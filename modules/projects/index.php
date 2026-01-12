<?php
/**
 * Projects List Page - Component Based
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Project.php';
require_once __DIR__ . '/../../includes/functions.php';

// Load Components
require_once __DIR__ . '/../../components/PageLayout.php';
require_once __DIR__ . '/../../components/Card.php';
require_once __DIR__ . '/../../components/Table.php';
require_once __DIR__ . '/../../components/Button.php';
require_once __DIR__ . '/../../components/Badge.php';
require_once __DIR__ . '/../../components/Input.php';
require_once __DIR__ . '/../../components/Select.php';

// Check authentication
requireLogin();

$auth = new Auth();
$projectClass = new Project();

// Get filter parameters
$filters = [
    'status' => $_GET['status'] ?? '',
    'priority' => $_GET['priority'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Get projects
$page = $_GET['page'] ?? 1;
$projects = $projectClass->getAll($page, RECORDS_PER_PAGE, $filters);
$totalProjects = $projectClass->getTotalCount($filters);
$totalPages = ceil($totalProjects / RECORDS_PER_PAGE);

// Build Page Content
ob_start();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0">Projects</h1>
            <p class="text-muted">Manage all your projects</p>
        </div>
        <div class="col-md-6 text-md-end">
            <?php if ($auth->hasRole(['admin', 'manager'])): ?>
                <?php echo Button::create([
                    'variant' => 'primary',
                    'icon' => 'fas fa-plus',
                    'onclick' => "window.location.href='create.php'"
                ], 'New Project'); ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="row mb-4">
        <div class="col-12">
            <?php
            ob_start();
            ?>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <?php echo Input::create([
                        'name' => 'search',
                        'type' => 'text',
                        'placeholder' => 'Search projects...',
                        'value' => $filters['search'],
                        'icon' => 'fas fa-search'
                    ]); ?>
                </div>
                
                <div class="col-md-3">
                    <?php echo Select::create([
                        'name' => 'status',
                        'placeholder' => 'All Statuses',
                        'value' => $filters['status'],
                        'options' => [
                            'planning' => 'Planning',
                            'active' => 'Active',
                            'on_hold' => 'On Hold',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled'
                        ]
                    ]); ?>
                </div>
                
                <div class="col-md-3">
                    <?php echo Select::create([
                        'name' => 'priority',
                        'placeholder' => 'All Priorities',
                        'value' => $filters['priority'],
                        'options' => [
                            'low' => 'Low',
                            'medium' => 'Medium',
                            'high' => 'High',
                            'critical' => 'Critical'
                        ]
                    ]); ?>
                </div>
                
                <div class="col-md-3">
                    <?php echo Button::create([
                        'type' => 'submit',
                        'variant' => 'primary',
                        'icon' => 'fas fa-filter'
                    ], 'Filter'); ?>
                    
                    <?php echo Button::create([
                        'variant' => 'secondary',
                        'icon' => 'fas fa-redo',
                        'onclick' => "window.location.href='index.php'"
                    ], 'Reset'); ?>
                </div>
            </form>
            <?php
            $filtersContent = ob_get_clean();
            
            echo Card::create([
                'title' => 'Filters',
                'bodyClass' => 'pb-0'
            ], $filtersContent);
            ?>
        </div>
    </div>

    <!-- Projects Table Card -->
    <div class="row">
        <div class="col-12">
            <?php
            // Build table actions
            $tableActions = function($row) use ($auth) {
                $html = '';
                
                // View button
                $html .= Button::create([
                    'variant' => 'info',
                    'size' => 'sm',
                    'icon' => 'fas fa-eye',
                    'onclick' => "window.location.href='view.php?id=" . $row['project_id'] . "'",
                    'class' => 'me-1'
                ], '');
                
                // Edit button (only for admin/manager)
                if ($auth->hasRole(['admin', 'manager'])) {
                    $html .= Button::create([
                        'variant' => 'warning',
                        'size' => 'sm',
                        'icon' => 'fas fa-edit',
                        'onclick' => "window.location.href='edit.php?id=" . $row['project_id'] . "'",
                        'class' => 'me-1'
                    ], '');
                }
                
                // Delete button (only for admin)
                if ($auth->hasRole('admin')) {
                    $html .= Button::create([
                        'variant' => 'danger',
                        'size' => 'sm',
                        'icon' => 'fas fa-trash',
                        'onclick' => "if(confirm('Delete this project?')) window.location.href='delete.php?id=" . $row['project_id'] . "'",
                        'class' => 'confirm-delete'
                    ], '');
                }
                
                return $html;
            };
            
            $projectsTable = Table::create([
                'columns' => [
                    [
                        'key' => 'project_code',
                        'label' => 'Code',
                        'width' => '100px'
                    ],
                    [
                        'key' => 'project_name',
                        'label' => 'Project Name',
                        'render' => function($value, $row) {
                            return '<strong>' . htmlspecialchars($value) . '</strong><br>'
                                . '<small class="text-muted">Manager: ' . htmlspecialchars($row['manager_name']) . '</small>';
                        }
                    ],
                    [
                        'key' => 'status',
                        'label' => 'Status',
                        'width' => '120px',
                        'render' => function($value) {
                            return Badge::create([
                                'variant' => getStatusBadge($value)
                            ], ucfirst($value));
                        }
                    ],
                    [
                        'key' => 'priority',
                        'label' => 'Priority',
                        'width' => '100px',
                        'render' => function($value) {
                            return Badge::create([
                                'variant' => getPriorityBadge($value),
                                'pill' => true
                            ], ucfirst($value));
                        }
                    ],
                    [
                        'key' => 'progress',
                        'label' => 'Progress',
                        'width' => '150px',
                        'render' => function($value) {
                            $variant = $value < 30 ? 'danger' : ($value < 70 ? 'warning' : 'success');
                            return '<div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-' . $variant . '" style="width: ' . $value . '%">'
                                . $value . '%</div>
                            </div>';
                        }
                    ],
                    [
                        'key' => 'team_size',
                        'label' => 'Team',
                        'width' => '80px',
                        'render' => function($value) {
                            return '<i class="fas fa-users"></i> ' . $value;
                        }
                    ],
                    [
                        'key' => 'start_date',
                        'label' => 'Start Date',
                        'width' => '120px',
                        'render' => function($value) {
                            return $value ? formatDate($value) : 'N/A';
                        }
                    ]
                ],
                'data' => $projects,
                'actions' => $tableActions
            ]);
            
            // Build pagination
            $pagination = '';
            if ($totalPages > 1) {
                $pagination .= '<nav class="mt-3"><ul class="pagination justify-content-end">';
                
                // Previous
                $prevDisabled = ($page <= 1) ? 'disabled' : '';
                $prevPage = max(1, $page - 1);
                $pagination .= '<li class="page-item ' . $prevDisabled . '">
                    <a class="page-link" href="?page=' . $prevPage . '&' . http_build_query($filters) . '">Previous</a>
                </li>';
                
                // Pages
                for ($i = 1; $i <= $totalPages; $i++) {
                    $active = ($i == $page) ? 'active' : '';
                    $pagination .= '<li class="page-item ' . $active . '">
                        <a class="page-link" href="?page=' . $i . '&' . http_build_query($filters) . '">' . $i . '</a>
                    </li>';
                }
                
                // Next
                $nextDisabled = ($page >= $totalPages) ? 'disabled' : '';
                $nextPage = min($totalPages, $page + 1);
                $pagination .= '<li class="page-item ' . $nextDisabled . '">
                    <a class="page-link" href="?page=' . $nextPage . '&' . http_build_query($filters) . '">Next</a>
                </li>';
                
                $pagination .= '</ul></nav>';
            }
            
            echo Card::create([
                'title' => 'All Projects',
                'subtitle' => 'Total: ' . $totalProjects . ' projects',
                'noPadding' => true
            ], $projectsTable . $pagination);
            ?>
        </div>
    </div>
</div>

<?php
$pageContent = ob_get_clean();

// Render page with layout
echo PageLayout::create([
    'title' => 'Projects',
    'auth' => $auth
], $pageContent);
