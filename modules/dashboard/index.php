<?php
/**
 * Dashboard Page - Component Based
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Project.php';
require_once __DIR__ . '/../../classes/Task.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../includes/functions.php';

// Load Components
require_once __DIR__ . '/../../components/PageLayout.php';
require_once __DIR__ . '/../../components/Card.php';
require_once __DIR__ . '/../../components/Table.php';
require_once __DIR__ . '/../../components/Badge.php';
require_once __DIR__ . '/../../components/Alert.php';
require_once __DIR__ . '/../../components/Button.php';

// Check authentication
requireLogin();

$auth = new Auth();
$currentUser = $auth->getCurrentUser();
$userId = $auth->getUserId();

// Initialize classes
$projectClass = new Project();
$taskClass = new Task();
$userClass = new User();

// Get statistics
$projectStats = $projectClass->getStatistics();
$taskStats = $taskClass->getStatistics();
$userStats = $userClass->getStatistics();

// Get user's tasks
$myTasks = $taskClass->getByAssignedUser($userId);
$overdueTasks = $taskClass->getOverdue($userId);

// Get recent projects
$recentProjects = $projectClass->getUserProjects($userId);
if (count($recentProjects) > 5) {
    $recentProjects = array_slice($recentProjects, 0, 5);
}


// Build Page Content using Components
ob_start();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">Dashboard</h1>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <?php if ($auth->hasRole(['admin', 'manager'])): ?>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Projects</div>
                            <div class="h5 mb-0 fw-bold"><?php echo $projectStats['total_projects'] ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-folder fa-2x text-secondary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Active Projects</div>
                            <div class="h5 mb-0 fw-bold"><?php echo $projectStats['active_projects'] ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-secondary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">My Tasks</div>
                            <div class="h5 mb-0 fw-bold"><?php echo count($myTasks); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-secondary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Overdue Tasks</div>
                            <div class="h5 mb-0 fw-bold"><?php echo count($overdueTasks); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-secondary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Projects Card -->
        <div class="col-lg-6 mb-4">
            <?php
            $projectsTable = empty($recentProjects) 
                ? '<p class="text-muted text-center py-4">No projects found</p>'
                : Table::create([
                    'columns' => [
                        [
                            'key' => 'project_name',
                            'label' => 'Project',
                            'render' => function($value, $row) {
                                return '<a href="../projects/view.php?id=' . $row['project_id'] . '">' 
                                    . htmlspecialchars($value) . '</a><br>'
                                    . '<small class="text-muted">' . htmlspecialchars($row['project_code']) . '</small>';
                            }
                        ],
                        [
                            'key' => 'status',
                            'label' => 'Status',
                            'render' => function($value) {
                                return Badge::create([
                                    'variant' => getStatusBadge($value)
                                ], ucfirst($value));
                            }
                        ],
                        [
                            'key' => 'progress',
                            'label' => 'Progress',
                            'render' => function($value) {
                                return '<div class="progress" style="height: 20px;">
                                    <div class="progress-bar" style="width: ' . $value . '%">' . $value . '%</div>
                                </div>';
                            }
                        ]
                    ],
                    'data' => $recentProjects
                ]);
            
            echo Card::create([
                'title' => 'Recent Projects',
                'headerActions' => Button::create([
                    'variant' => 'primary',
                    'size' => 'sm',
                    'onclick' => "window.location.href='../projects/'"
                ], 'View All')
            ], $projectsTable);
            ?>
        </div>

        <!-- My Tasks Card -->
        <div class="col-lg-6 mb-4">
            <?php
            $displayTasks = array_slice($myTasks, 0, 5);
            $tasksTable = empty($displayTasks)
                ? '<p class="text-muted text-center py-4">No tasks assigned</p>'
                : Table::create([
                    'columns' => [
                        [
                            'key' => 'task_name',
                            'label' => 'Task',
                            'render' => function($value, $row) {
                                return '<a href="../tasks/view.php?id=' . $row['task_id'] . '">' 
                                    . htmlspecialchars($value) . '</a><br>'
                                    . '<small class="text-muted">' . htmlspecialchars($row['project_name']) . '</small>';
                            }
                        ],
                        [
                            'key' => 'priority',
                            'label' => 'Priority',
                            'width' => '100px',
                            'render' => function($value) {
                                return Badge::create([
                                    'variant' => getPriorityBadge($value)
                                ], ucfirst($value));
                            }
                        ],
                        [
                            'key' => 'status',
                            'label' => 'Status',
                            'width' => '120px',
                            'render' => function($value) {
                                return Badge::create([
                                    'variant' => getStatusBadge($value)
                                ], str_replace('_', ' ', ucfirst($value)));
                            }
                        ],
                        [
                            'key' => 'due_date',
                            'label' => 'Due Date',
                            'width' => '120px',
                            'render' => function($value, $row) {
                                if (!$value) return 'N/A';
                                $isOverdue = strtotime($value) < time() && $row['status'] != 'completed';
                                return formatDate($value) . ($isOverdue ? ' <i class="fas fa-exclamation-circle text-danger"></i>' : '');
                            }
                        ]
                    ],
                    'data' => $displayTasks
                ]);
            
            echo Card::create([
                'title' => 'My Tasks',
                'headerActions' => Button::create([
                    'variant' => 'primary',
                    'size' => 'sm',
                    'onclick' => "window.location.href='../tasks/'"
                ], 'View All')
            ], $tasksTable);
            ?>
        </div>
    </div>

    <!-- Overdue Tasks Alert -->
    <?php if (!empty($overdueTasks)): ?>
    <div class="row">
        <div class="col-12">
            <?php echo Alert::create([
                'variant' => 'warning',
                'icon' => 'fas fa-exclamation-triangle',
                'dismissible' => false
            ], '
                <strong>Overdue Tasks</strong><br>
                You have ' . count($overdueTasks) . ' overdue task(s). Please review and update them.
                <br><br>
                ' . Button::create([
                    'variant' => 'warning',
                    'size' => 'sm',
                    'onclick' => "window.location.href='../tasks/?filter=overdue'"
                ], 'View Overdue Tasks') . '
            '); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    .border-left-primary { border-left: 4px solid #4e73df !important; }
    .border-left-success { border-left: 4px solid #1cc88a !important; }
    .border-left-info { border-left: 4px solid #36b9cc !important; }
    .border-left-warning { border-left: 4px solid #f6c23e !important; }
</style>

<?php
$pageContent = ob_get_clean();

// Render page with layout
echo PageLayout::create([
    'title' => 'Dashboard',
    'auth' => $auth
], $pageContent);
