<?php
/**
 * Page Layout Component
 * Main layout wrapper for all pages with header, sidebar, and content area
 * Usage: PageLayout::create(['title' => 'Dashboard', 'auth' => $auth], $pageContent)
 */

require_once __DIR__ . '/Component.php';
require_once __DIR__ . '/../includes/functions.php';

class PageLayout extends Component {
    
    public function render() {
        $title = $this->prop('title', 'Dashboard');
        $auth = $this->prop('auth');
        $currentUser = $auth ? $auth->getCurrentUser() : null;
        
        if (!$currentUser) {
            return '<p>Please login</p>';
        }
        
        $appName = APP_NAME;
        
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $this->escape($title) . ' - ' . $this->escape($appName) . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fc; }
        .sidebar { position: fixed; top: 0; left: 0; height: 100vh; width: 250px; background: linear-gradient(180deg, #667eea 0%, #764ba2 100%); padding-top: 20px; z-index: 1000; overflow-y: auto; }
        .sidebar-brand { padding: 15px 20px; color: white; font-size: 1.3rem; font-weight: bold; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.2); margin-bottom: 10px; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li { margin: 5px 0; }
        .sidebar-menu a { display: block; padding: 12px 20px; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,0.1); color: white; border-left: 4px solid white; }
        .sidebar-menu i { width: 20px; margin-right: 10px; }
        .main-content { margin-left: 250px; padding: 20px; }
        .top-navbar { background: white; padding: 15px 30px; margin: -20px -20px 20px -20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        @media (max-width: 768px) { .sidebar { width: 0; overflow: hidden; } .sidebar.active { width: 250px; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>';
        
        // Sidebar
        $html .= '<div class="sidebar" id="sidebar">';
        $html .= '<div class="sidebar-brand"><i class="fas fa-tasks"></i> ' . $this->escape($appName) . '</div>';
        $html .= '<ul class="sidebar-menu">';
        
        $currentPath = $_SERVER['PHP_SELF'];
        
        $menuItems = [
            ['href' => '/modules/dashboard/index.php', 'icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'path' => 'dashboard'],
            ['href' => '/modules/projects/index.php', 'icon' => 'fas fa-folder', 'label' => 'Projects', 'path' => 'projects'],
            ['href' => '/modules/tasks/index.php', 'icon' => 'fas fa-tasks', 'label' => 'Tasks', 'path' => 'tasks'],
        ];
        
        if ($auth->hasRole(['admin', 'manager'])) {
            $menuItems[] = ['href' => '/modules/users/index.php', 'icon' => 'fas fa-users', 'label' => 'Users', 'path' => 'users'];
            $menuItems[] = ['href' => '/modules/reports/index.php', 'icon' => 'fas fa-chart-bar', 'label' => 'Reports', 'path' => 'reports'];
        }
        
        if ($auth->hasRole('admin')) {
            $menuItems[] = ['href' => '/modules/settings/index.php', 'icon' => 'fas fa-cog', 'label' => 'Settings', 'path' => 'settings'];
        }
        
        foreach ($menuItems as $item) {
            $active = (strpos($currentPath, $item['path']) !== false) ? 'active' : '';
            $html .= '<li><a href="' . $item['href'] . '" class="' . $active . '">';
            $html .= '<i class="' . $item['icon'] . '"></i> ' . $item['label'];
            $html .= '</a></li>';
        }
        
        $html .= '<li style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 10px;">';
        $html .= '<a href="/modules/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>';
        $html .= '</li>';
        $html .= '</ul>';
        $html .= '</div>';
        
        // Main Content
        $html .= '<div class="main-content">';
        
        // Top Navbar
        $html .= '<div class="top-navbar d-flex justify-content-between align-items-center">';
        $html .= '<div><button class="btn btn-sm btn-outline-secondary d-md-none" id="sidebarToggle"><i class="fas fa-bars"></i></button></div>';
        $html .= '<div class="dropdown user-dropdown">';
        $html .= '<a class="dropdown-toggle text-decoration-none text-dark" href="#" role="button" data-bs-toggle="dropdown">';
        $html .= '<i class="fas fa-user-circle fa-lg"></i>';
        $html .= '<span class="ms-2">' . $this->escape($currentUser['full_name']) . '</span>';
        $html .= '<span class="badge bg-primary">' . ucfirst($currentUser['role']) . '</span>';
        $html .= '</a>';
        $html .= '<ul class="dropdown-menu dropdown-menu-end">';
        $html .= '<li><a class="dropdown-item" href="/modules/profile/index.php"><i class="fas fa-user"></i> Profile</a></li>';
        $html .= '<li><a class="dropdown-item" href="/modules/settings/index.php"><i class="fas fa-cog"></i> Settings</a></li>';
        $html .= '<li><hr class="dropdown-divider"></li>';
        $html .= '<li><a class="dropdown-item" href="/modules/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>';
        $html .= '</ul>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Flash Messages
        $flashMessage = getFlashMessage();
        if ($flashMessage) {
            require_once __DIR__ . '/Alert.php';
            $html .= Alert::create([
                'variant' => $flashMessage['type'],
                'dismissible' => true
            ], $flashMessage['message']);
        }
        
        // Page Content
        $html .= $this->renderChildren();
        
        $html .= '</div>'; // End main-content
        
        // Scripts
        $html .= '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>';
        $html .= '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
        $html .= '<script>
            document.getElementById("sidebarToggle")?.addEventListener("click", function() {
                document.getElementById("sidebar").classList.toggle("active");
            });
            setTimeout(function() {
                const alerts = document.querySelectorAll(".alert");
                alerts.forEach(function(alert) { new bootstrap.Alert(alert).close(); });
            }, 5000);
        </script>';
        $html .= '</body></html>';
        
        return $html;
    }
}
