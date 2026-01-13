<?php
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
    // We're in admin folder, go up one level
    $project_root = dirname($current_dir);
} else {
    // We're in root folder
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

// Helper function for navigation links with proper cross-directory support
function nav_url($path) {
    global $base_url;
    
    // Remove leading slash if present
    $path = ltrim($path, '/');
    
    // Always use base_url + path for consistent navigation
    return $base_url . '/' . $path;
}

// Improved active class detection
function is_active($check_file, $check_prefix = null) {
    global $current_file, $current_full_path, $is_admin_section;
    
    // Handle prefix-based matching (like project-, task-, etc.)
    if ($check_prefix && strpos($current_file, $check_prefix) === 0) {
        return 'active';
    }
    
    // Handle exact file matching
    if ($current_file === $check_file) {
        return 'active';
    }
    
    // Special handling for admin section
    if ($check_file === 'admin-section' && $is_admin_section) {
        return 'active';
    }
    
    return '';
}

// Helper function to check admin section active states
function is_admin_active($check_file = null) {
    global $current_file, $is_admin_section;
    
    // If we're not in admin section, never active
    if (!$is_admin_section) {
        return '';
    }
    
    // If checking for general admin active state
    if ($check_file === null) {
        return 'active';
    }
    
    // Check specific file in admin section
    if ($current_file === $check_file) {
        return 'active';
    }
    
    return '';
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        .main-content {
            padding-top: 70px;
            min-height: 100vh;
        }
        
        /* NAVBAR */
        .navbar-inverse {
            background: rgba(255, 255, 255, 0.98);
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 0;
            min-height: 65px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            width: 100%;
        }
        
        .navbar-inverse .navbar-brand {
            color: #667eea;
            font-weight: 700;
            font-size: 20px;
            padding: 20px 15px;
            transition: color 0.2s;
        }
        
        .navbar-inverse .navbar-brand:hover {
            color: #764ba2;
        }
        
        .navbar-inverse .navbar-nav > li > a {
            color: #374151;
            font-weight: 600;
            font-size: 14px;
            padding: 20px 16px;
            transition: color 0.2s;
        }
        
        .navbar-inverse .navbar-nav > li.active > a,
        .navbar-inverse .navbar-nav > li > a:hover,
        .navbar-inverse .navbar-nav > li > a:focus {
            color: #667eea;
            background: transparent;
        }
        
        .navbar-inverse .navbar-nav > li.active > a {
            border-bottom: 3px solid #667eea;
        }
        
        /* DROPDOWN */
        .navbar-inverse .navbar-nav .dropdown-menu {
            background: white;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            padding: 8px 0;
            margin-top: 0;
        }
        
        .navbar-inverse .navbar-nav .dropdown-menu > li > a {
            padding: 10px 20px;
            color: #374151;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .navbar-inverse .navbar-nav .dropdown-menu > li.active > a,
        .navbar-inverse .navbar-nav .dropdown-menu > li > a:hover {
            background: #f8fafc;
            color: #667eea;
        }
        
        .navbar-inverse .navbar-nav .dropdown-menu > li > a i {
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }
        
        .navbar-inverse .navbar-nav .dropdown-menu .divider {
            height: 1px;
            margin: 8px 0;
            background-color: #e2e8f0;
        }
        
        .navbar-inverse .navbar-nav.navbar-right .dropdown-menu {
            right: 0;
            left: auto;
        }
        
        .navbar-inverse .navbar-nav.navbar-right .dropdown-toggle {
            background: #f8fafc;
            border-radius: 20px;
            padding: 10px 16px;
            margin-top: 10px;
            transition: all 0.2s;
        }
        
        .navbar-inverse .navbar-nav.navbar-right .dropdown-toggle:hover {
            background: #667eea;
            color: white;
        }
        
        .navbar-inverse .navbar-toggle {
            border: 2px solid #667eea;
            border-radius: 6px;
            margin-top: 15px;
            margin-right: 15px;
        }
        
        .navbar-inverse .navbar-toggle:hover,
        .navbar-inverse .navbar-toggle:focus {
            background: #667eea;
        }
        
        .navbar-inverse .navbar-toggle .icon-bar {
            background-color: #667eea;
        }
        
        .navbar-inverse .navbar-toggle:hover .icon-bar,
        .navbar-inverse .navbar-toggle:focus .icon-bar {
            background-color: white;
        }
        
        /* RESPONSIVE */
        @media (max-width: 767px) {
            .main-content { padding-top: 60px; }
            .navbar-inverse { min-height: 60px; }
            .navbar-inverse .navbar-brand {
                font-size: 18px;
                padding: 15px;
            }
            .navbar-inverse .navbar-nav {
                background: white;
                border-radius: 8px;
                padding: 10px 0;
                margin: 10px 15px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .navbar-inverse .navbar-nav > li > a {
                border-radius: 6px;
                margin: 2px 10px;
                padding: 10px 15px;
            }
            .navbar-inverse .navbar-nav > li.active > a {
                background: #f8fafc;
                border-bottom: none;
            }
            .navbar-inverse .navbar-nav .dropdown-menu {
                position: static;
                float: none;
                width: auto;
                margin: 5px 10px;
                background: #f8fafc;
                box-shadow: none;
            }
            .navbar-inverse .navbar-nav.navbar-right .dropdown-toggle {
                margin: 2px 10px;
                padding: 10px 15px;
            }
        }
        
        .container-fluid {
            padding-left: 15px;
            padding-right: 15px;
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
            // Adjust content padding
            function adjustContent() {
                const navHeight = $('.navbar-fixed-top').outerHeight();
                $('.main-content').css('padding-top', (navHeight + 15) + 'px');
            }
            
            setTimeout(adjustContent, 100);
            $(window).on('resize', adjustContent);
            
            // Mobile menu handling
            $('.navbar-nav li a').on('click', function(e) {
                const $this = $(this);
                const href = $this.attr('href');
                
                if (!$this.hasClass('dropdown-toggle') && href && href !== '#') {
                    if ($(window).width() < 768) {
                        $('.navbar-collapse').collapse('hide');
                    }
                }
            });
            
            // Desktop dropdown hover
            if ($(window).width() >= 768) {
                $('.navbar-nav .dropdown').hover(
                    function() { $(this).addClass('open'); },
                    function() { $(this).removeClass('open'); }
                );
            }
            
            // Mobile dropdown click
            $('.navbar-nav .dropdown-toggle').on('click', function(e) {
                if ($(window).width() < 768) {
                    e.preventDefault();
                    const $dropdown = $(this).parent();
                    $('.navbar-nav .dropdown').not($dropdown).removeClass('open');
                    $dropdown.toggleClass('open');
                }
            });
            
            // Close dropdowns on outside click
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.navbar-nav .dropdown').length) {
                    $('.navbar-nav .dropdown').removeClass('open');
                }
            });
        });
    </script>
</body>
</html>