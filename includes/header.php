<?php
// START OUTPUT BUFFERING
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}

// DETERMINE AUTH COMPONENT PATH DYNAMICALLY
$current_dir = dirname(__FILE__);
if (file_exists($current_dir . '/../components/auth.php')) {
    require_once $current_dir . '/../components/auth.php';
} elseif (file_exists($current_dir . '/components/auth.php')) {
    require_once $current_dir . '/components/auth.php';
} else {
    require_once __DIR__ . '/../components/auth.php';
}

$auth = new Auth();

// ROBUST BASE URL DETECTION
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Get the directory of the current script
$current_dir = dirname($script_name);

// Determine if we're in admin subdirectory
$is_in_admin_dir = (basename($current_dir) === 'admin');

// Calculate project root path
if ($is_in_admin_dir) {
    $project_root = dirname($current_dir);
} else {
    $project_root = $current_dir;
}

// Ensure project root starts with / and doesn't end with /
$project_root = '/' . trim($project_root, '/');
if ($project_root === '/') {
    $project_root = '';
}

$base_url = $protocol . "://" . $host . $project_root;

// Get current page info
$current_file = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['REQUEST_URI'];
$current_full_path = $_SERVER['PHP_SELF'];

// Check sections with improved logic
$is_admin_section = strpos($current_full_path, '/admin/') !== false;
$is_project_page = (strpos($current_file, 'project-') === 0 || $current_file === 'projects.php') && !$is_admin_section;
$is_task_page = (strpos($current_file, 'task-') === 0 || $current_file === 'tasks.php') && !$is_admin_section;
$is_user_page = (strpos($current_file, 'user-') === 0 || $current_file === 'users.php') && $is_admin_section;
$is_pricing_page = strpos($current_file, 'pricing-') === 0 && !$is_admin_section;
$is_requirement_page = strpos($current_file, 'requirement-') === 0 && !$is_admin_section;

// Helper function for navigation links
function nav_url($path) {
    global $base_url;
    $path = ltrim($path, '/');
    return $base_url . '/' . $path;
}

// Active class detection
function is_active($check_file, $check_prefix = null) {
    global $current_file, $current_full_path, $is_admin_section;
    
    if ($check_prefix && strpos($current_file, $check_prefix) === 0) {
        return 'active';
    }
    
    if ($current_file === $check_file) {
        return 'active';
    }
    
    if ($check_file === 'admin-section' && $is_admin_section) {
        return 'active';
    }
    
    return '';
}

// Admin section active states
function is_admin_active($check_file = null) {
    global $current_file, $is_admin_section;
    
    if (!$is_admin_section) {
        return '';
    }
    
    if ($check_file === null) {
        return 'active';
    }
    
    if ($current_file === $check_file) {
        return 'active';
    }
    
    return '';
}

// FLUSH OUTPUT BUFFER
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Project Management System</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* MODERN PROFESSIONAL DESIGN SYSTEM */
        
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
        }
        
        * {
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #e6f0fa 100%);
            background-attachment: fixed;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            color: var(--dark);
        }
        
        .main-content {
            padding-top: 72px;
            min-height: 100vh;
        }
        
        /* MODERN NAVBAR */
        .navbar-inverse {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border: none;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            border-bottom: 1px solid var(--border);
            margin-bottom: 0;
            min-height: 68px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
        }
        
        .navbar-inverse.scrolled {
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.12);
            background: rgba(255, 255, 255, 1);
            min-height: 64px;
        }
        
        .navbar-inverse .navbar-brand {
            color: var(--primary);
            font-weight: 800;
            font-size: 24px;
            letter-spacing: -0.5px;
            padding: 22px 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar-inverse .navbar-brand:hover,
        .navbar-inverse .navbar-brand:focus {
            color: var(--primary-dark);
            transform: scale(1.05);
        }
        
        .navbar-inverse .navbar-brand i {
            font-size: 26px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* NAVBAR LINKS */
        .navbar-inverse .navbar-nav > li > a {
            color: #475569;
            font-weight: 600;
            font-size: 14px;
            padding: 22px 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        
        .navbar-inverse .navbar-nav > li > a::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transform: translateX(-50%);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 3px 3px 0 0;
        }
        
        .navbar-inverse .navbar-nav > li > a:hover::before,
        .navbar-inverse .navbar-nav > li.active > a::before {
            width: 75%;
        }
        
        .navbar-inverse .navbar-nav > li > a:hover,
        .navbar-inverse .navbar-nav > li.active > a,
        .navbar-inverse .navbar-nav > li.active > a:hover,
        .navbar-inverse .navbar-nav > li.active > a:focus {
            color: var(--primary);
            background: transparent;
        }
        
        .navbar-inverse .navbar-nav > li > a i {
            margin-right: 7px;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 15px;
        }
        
        .navbar-inverse .navbar-nav > li > a:hover i {
            transform: scale(1.15);
        }
        
        /* DROPDOWN MENU */
        .navbar-inverse .navbar-nav .dropdown-menu {
            background: white;
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
            border-radius: 12px;
            padding: 8px;
            margin-top: 10px;
            min-width: 240px;
            animation: dropdownSlide 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes dropdownSlide {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .navbar-inverse .navbar-nav .dropdown-menu > li > a {
            padding: 12px 16px;
            color: #475569;
            font-weight: 600;
            font-size: 14px;
            border-radius: 8px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .navbar-inverse .navbar-nav .dropdown-menu > li > a:hover,
        .navbar-inverse .navbar-nav .dropdown-menu > li.active > a,
        .navbar-inverse .navbar-nav .dropdown-menu > li.active > a:hover,
        .navbar-inverse .navbar-nav .dropdown-menu > li.active > a:focus {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
            color: var(--primary);
            transform: translateX(4px);
        }
        
        .navbar-inverse .navbar-nav .dropdown-menu > li > a i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }
        
        .navbar-inverse .navbar-nav .dropdown-menu .divider {
            height: 1px;
            margin: 8px 0;
            background: var(--border);
        }
        
        /* USER DROPDOWN */
        .navbar-inverse .navbar-nav.navbar-right .dropdown-toggle {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
            border-radius: 12px;
            padding: 10px 18px;
            margin-top: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .navbar-inverse .navbar-nav.navbar-right .dropdown-toggle:hover,
        .navbar-inverse .navbar-nav.navbar-right .dropdown-toggle:focus,
        .navbar-inverse .navbar-nav.navbar-right .open .dropdown-toggle {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        /* MOBILE TOGGLE */
        .navbar-inverse .navbar-toggle {
            border: 2px solid var(--primary);
            border-radius: 10px;
            margin-top: 16px;
            margin-right: 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 9px 10px;
        }
        
        .navbar-inverse .navbar-toggle:hover,
        .navbar-inverse .navbar-toggle:focus {
            background: var(--primary);
            transform: scale(1.05);
        }
        
        .navbar-inverse .navbar-toggle .icon-bar {
            background-color: var(--primary);
            transition: all 0.3s ease;
            height: 3px;
            border-radius: 2px;
        }
        
        .navbar-inverse .navbar-toggle:hover .icon-bar,
        .navbar-inverse .navbar-toggle:focus .icon-bar {
            background-color: white;
        }
        
        /* DROPDOWN CARET ANIMATION */
        .navbar-inverse .navbar-nav .dropdown .caret {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .navbar-inverse .navbar-nav .dropdown.open .caret {
            transform: rotate(180deg);
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
        @media (max-width: 767px) {
            .main-content {
                padding-top: 60px;
            }
            
            .navbar-inverse {
                min-height: 56px;
            }
            
            .navbar-inverse .navbar-brand {
                font-size: 20px;
                padding: 16px 15px;
            }
            
            .navbar-inverse .navbar-brand i {
                font-size: 22px;
            }
            
            .navbar-inverse .navbar-toggle {
                margin-top: 10px;
            }
            
            .navbar-inverse .navbar-nav {
                margin: 8px 15px;
                background: white;
                border-radius: 12px;
                padding: 12px 0;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            }
            
            .navbar-inverse .navbar-nav > li > a {
                border-radius: 8px;
                margin: 4px 12px;
                padding: 12px 16px;
            }
            
            .navbar-inverse .navbar-nav > li > a::before {
                display: none;
            }
            
            .navbar-inverse .navbar-nav > li.active > a,
            .navbar-inverse .navbar-nav > li.active > a:hover,
            .navbar-inverse .navbar-nav > li.active > a:focus {
                background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
                color: var(--primary);
            }
            
            .navbar-inverse .navbar-nav .dropdown-menu {
                position: static;
                float: none;
                width: auto;
                margin: 4px 12px;
                background: #f8fafc;
                box-shadow: none;
                border: none;
                animation: none;
            }
            
            .navbar-inverse .navbar-nav .dropdown-menu > li > a {
                padding: 10px 16px;
            }
            
            .navbar-inverse .navbar-nav.navbar-right .dropdown-toggle {
                margin: 4px 12px;
                padding: 12px 16px;
            }
            
            .navbar-inverse .navbar-nav.navbar-right .dropdown-toggle:hover,
            .navbar-inverse .navbar-nav.navbar-right .dropdown-toggle:focus,
            .navbar-inverse .navbar-nav.navbar-right .open .dropdown-toggle {
                transform: none;
            }
        }
        
        @media (max-width: 480px) {
            .navbar-inverse .navbar-brand {
                font-size: 18px;
                padding: 16px 12px;
            }
            
            .navbar-inverse .navbar-brand i {
                font-size: 20px;
            }
            
            .navbar-inverse .navbar-nav {
                margin: 8px 10px;
            }
            
            .navbar-inverse .navbar-nav > li > a,
            .navbar-inverse .navbar-nav .dropdown-menu > li > a,
            .navbar-inverse .navbar-nav.navbar-right .dropdown-toggle {
                font-size: 13px;
                padding: 10px 14px;
            }
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
                        <li class="dropdown <?php echo is_admin_active(); ?>">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <i class="fa fa-cog"></i> Admin <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="<?php echo is_admin_active('users.php'); ?>">
                                    <a href="<?php echo nav_url('admin/users.php'); ?>">
                                        <i class="fa fa-users"></i> Users
                                    </a>
                                </li>
                                <li class="<?php echo is_admin_active('analytics.php'); ?>">
                                    <a href="<?php echo nav_url('admin/analytics.php'); ?>">
                                        <i class="fa fa-bar-chart"></i> Analytics
                                    </a>
                                </li>
                                <li class="<?php echo is_admin_active('activity.php'); ?>">
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
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
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
            // NAVBAR SCROLL EFFECT
            $(window).on('scroll', function() {
                if ($(window).scrollTop() > 50) {
                    $('.navbar-inverse').addClass('scrolled');
                } else {
                    $('.navbar-inverse').removeClass('scrolled');
                }
            });
            
            // MOBILE NAVBAR COLLAPSE ON LINK CLICK
            $('.navbar-nav a:not(.dropdown-toggle)').on('click', function() {
                if ($(window).width() < 768) {
                    $('.navbar-collapse').collapse('hide');
                }
            });
            
            // DESKTOP DROPDOWN HOVER EFFECT
            if ($(window).width() >= 768) {
                $('.navbar-nav .dropdown').hover(
                    function() { 
                        $(this).addClass('open'); 
                    },
                    function() { 
                        $(this).removeClass('open'); 
                    }
                );
            }
            
            // CLOSE DROPDOWNS ON OUTSIDE CLICK
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.navbar-nav .dropdown').length) {
                    $('.navbar-nav .dropdown').removeClass('open');
                }
            });
            
            // SMOOTH SCROLL FOR ANCHOR LINKS
            $('a[href^="#"]').on('click', function(e) {
                var target = $(this.getAttribute('href'));
                if(target.length) {
                    e.preventDefault();
                    $('html, body').stop().animate({
                        scrollTop: target.offset().top - 80
                    }, 600);
                }
            });
            
            // PREVENT DROPDOWN TOGGLE FROM CLOSING ON SELF CLICK
            $('.navbar-nav .dropdown-toggle').on('click', function(e) {
                var $dropdown = $(this).parent();
                if ($(window).width() >= 768) {
                    e.preventDefault();
                    $('.navbar-nav .dropdown').not($dropdown).removeClass('open');
                    $dropdown.toggleClass('open');
                }
            });
        });
    </script>
</body>
</html>