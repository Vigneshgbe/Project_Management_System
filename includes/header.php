<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../components/auth.php';
$auth = new Auth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Project Management System</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
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
                <a class="navbar-brand" href="/dashboard.php">PM System</a>
            </div>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="nav navbar-nav">
                    <li <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>
                        <a href="/dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a>
                    </li>
                    <li <?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'class="active"' : ''; ?>>
                        <a href="/projects.php"><i class="fa fa-folder"></i> Projects</a>
                    </li>
                    <li <?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'class="active"' : ''; ?>>
                        <a href="/tasks.php"><i class="fa fa-tasks"></i> My Tasks</a>
                    </li>
                    <?php if ($auth->isAdmin()): ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-cog"></i> Admin <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="/admin/users.php"><i class="fa fa-users"></i> Users</a></li>
                            <li><a href="/admin/analytics.php"><i class="fa fa-bar-chart"></i> Analytics</a></li>
                            <li><a href="/admin/activity.php"><i class="fa fa-history"></i> Activity Log</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-user"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?> <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="/profile.php"><i class="fa fa-user"></i> Profile</a></li>
                            <li class="divider"></li>
                            <li><a href="/logout.php"><i class="fa fa-sign-out"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div style="margin-top: 70px;"></div>
    <?php endif; ?>
