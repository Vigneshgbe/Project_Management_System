<?php
/**
 * Common Header File
 */
if (!isset($auth)) {
    $auth = new Auth();
}
$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fc;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            padding-top: 20px;
            z-index: 1000;
            overflow-y: auto;
        }
        .sidebar-brand {
            padding: 15px 20px;
            color: white;
            font-size: 1.3rem;
            font-weight: bold;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 10px;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-menu li {
            margin: 5px 0;
        }
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid white;
        }
        .sidebar-menu i {
            width: 20px;
            margin-right: 10px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .top-navbar {
            background: white;
            padding: 15px 30px;
            margin: -20px -20px 20px -20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .user-dropdown {
            cursor: pointer;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }
            .sidebar.active {
                width: 250px;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-tasks"></i> <?php echo APP_NAME; ?>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="/modules/dashboard/index.php" <?php echo (strpos($_SERVER['PHP_SELF'], 'dashboard') !== false) ? 'class="active"' : ''; ?>>
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <li>
                <a href="/modules/projects/index.php" <?php echo (strpos($_SERVER['PHP_SELF'], 'projects') !== false) ? 'class="active"' : ''; ?>>
                    <i class="fas fa-folder"></i> Projects
                </a>
            </li>
            
            <li>
                <a href="/modules/tasks/index.php" <?php echo (strpos($_SERVER['PHP_SELF'], 'tasks') !== false) ? 'class="active"' : ''; ?>>
                    <i class="fas fa-tasks"></i> Tasks
                </a>
            </li>
            
            <?php if ($auth->hasRole(['admin', 'manager'])): ?>
            <li>
                <a href="/modules/users/index.php" <?php echo (strpos($_SERVER['PHP_SELF'], 'users') !== false) ? 'class="active"' : ''; ?>>
                    <i class="fas fa-users"></i> Users
                </a>
            </li>
            
            <li>
                <a href="/modules/reports/index.php" <?php echo (strpos($_SERVER['PHP_SELF'], 'reports') !== false) ? 'class="active"' : ''; ?>>
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($auth->hasRole('admin')): ?>
            <li>
                <a href="/modules/settings/index.php" <?php echo (strpos($_SERVER['PHP_SELF'], 'settings') !== false) ? 'class="active"' : ''; ?>>
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
            <?php endif; ?>
            
            <li style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 10px;">
                <a href="/modules/auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar d-flex justify-content-between align-items-center">
            <div>
                <button class="btn btn-sm btn-outline-secondary d-md-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div class="dropdown user-dropdown">
                <a class="dropdown-toggle text-decoration-none text-dark" href="#" role="button" 
                   data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle fa-lg"></i>
                    <span class="ms-2"><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                    <span class="badge bg-primary"><?php echo ucfirst($currentUser['role']); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="/modules/profile/index.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="/modules/settings/index.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/modules/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php 
        $flashMessage = getFlashMessage();
        if ($flashMessage): 
        ?>
        <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flashMessage['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Page Content -->
