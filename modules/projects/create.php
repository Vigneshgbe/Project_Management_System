<?php
/**
 * Create Project Page - Component Based
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Project.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../includes/functions.php';

// Load Components
require_once __DIR__ . '/../../components/PageLayout.php';
require_once __DIR__ . '/../../components/Card.php';
require_once __DIR__ . '/../../components/Button.php';
require_once __DIR__ . '/../../components/Input.php';
require_once __DIR__ . '/../../components/Select.php';
require_once __DIR__ . '/../../components/Textarea.php';
require_once __DIR__ . '/../../components/Alert.php';

// Check authentication and permissions
requireLogin();
requireRole(['admin', 'manager']);

$auth = new Auth();
$projectClass = new Project();
$userClass = new User();

// Get managers and clients for dropdowns
$managers = $userClass->getByRole('manager');
$clients = $userClass->getByRole('client');

$errors = [];
$formData = [];

// Handle form submission
if (isPost()) {
    $formData = [
        'project_name' => sanitize($_POST['project_name'] ?? ''),
        'project_code' => sanitize($_POST['project_code'] ?? ''),
        'description' => sanitize($_POST['description'] ?? ''),
        'client_id' => sanitize($_POST['client_id'] ?? ''),
        'manager_id' => sanitize($_POST['manager_id'] ?? ''),
        'start_date' => sanitize($_POST['start_date'] ?? ''),
        'end_date' => sanitize($_POST['end_date'] ?? ''),
        'estimated_hours' => sanitize($_POST['estimated_hours'] ?? ''),
        'budget' => sanitize($_POST['budget'] ?? ''),
        'status' => sanitize($_POST['status'] ?? 'planning'),
        'priority' => sanitize($_POST['priority'] ?? 'medium')
    ];
    
    // Validation
    if (empty($formData['project_name'])) {
        $errors['project_name'] = 'Project name is required';
    }
    
    if (empty($formData['project_code'])) {
        $errors['project_code'] = 'Project code is required';
    }
    
    if (empty($formData['manager_id'])) {
        $errors['manager_id'] = 'Manager is required';
    }
    
    // If no errors, create project
    if (empty($errors)) {
        $result = $projectClass->create($formData);
        
        if ($result['success']) {
            logActivity($auth->getUserId(), 'create', 'project', $result['project_id'], 'Created project: ' . $formData['project_name']);
            setFlashMessage('Project created successfully!', 'success');
            redirect('/modules/projects/view.php?id=' . $result['project_id']);
        } else {
            $errors['_general'] = $result['message'];
        }
    }
}

// Build Page Content
ob_start();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0">Create New Project</h1>
            <p class="text-muted">Add a new project to the system</p>
        </div>
        <div class="col-md-6 text-md-end">
            <?php echo Button::create([
                'variant' => 'secondary',
                'icon' => 'fas fa-arrow-left',
                'onclick' => "window.location.href='index.php'"
            ], 'Back to Projects'); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <?php
            // General error alert
            if (isset($errors['_general'])) {
                echo Alert::create([
                    'variant' => 'danger',
                    'dismissible' => true
                ], $errors['_general']);
            }
            
            ob_start();
            ?>
            
            <form method="POST" action="">
                <div class="row">
                    <!-- Project Name -->
                    <div class="col-md-6">
                        <?php echo Input::create([
                            'name' => 'project_name',
                            'label' => 'Project Name',
                            'placeholder' => 'Enter project name',
                            'value' => $formData['project_name'] ?? '',
                            'required' => true,
                            'error' => $errors['project_name'] ?? null,
                            'icon' => 'fas fa-project-diagram'
                        ]); ?>
                    </div>
                    
                    <!-- Project Code -->
                    <div class="col-md-6">
                        <?php echo Input::create([
                            'name' => 'project_code',
                            'label' => 'Project Code',
                            'placeholder' => 'e.g., PROJ-001',
                            'value' => $formData['project_code'] ?? '',
                            'required' => true,
                            'error' => $errors['project_code'] ?? null,
                            'icon' => 'fas fa-barcode'
                        ]); ?>
                    </div>
                </div>

                <!-- Description -->
                <?php echo Textarea::create([
                    'name' => 'description',
                    'label' => 'Description',
                    'placeholder' => 'Enter project description...',
                    'value' => $formData['description'] ?? '',
                    'rows' => 4
                ]); ?>

                <div class="row">
                    <!-- Manager -->
                    <div class="col-md-6">
                        <?php 
                        $managerOptions = [];
                        foreach ($managers as $manager) {
                            $managerOptions[$manager['user_id']] = $manager['full_name'];
                        }
                        
                        echo Select::create([
                            'name' => 'manager_id',
                            'label' => 'Project Manager',
                            'options' => $managerOptions,
                            'value' => $formData['manager_id'] ?? '',
                            'required' => true,
                            'error' => $errors['manager_id'] ?? null,
                            'placeholder' => '-- Select Manager --'
                        ]); 
                        ?>
                    </div>
                    
                    <!-- Client -->
                    <div class="col-md-6">
                        <?php 
                        $clientOptions = [];
                        foreach ($clients as $client) {
                            $clientOptions[$client['user_id']] = $client['full_name'];
                        }
                        
                        echo Select::create([
                            'name' => 'client_id',
                            'label' => 'Client',
                            'options' => $clientOptions,
                            'value' => $formData['client_id'] ?? '',
                            'placeholder' => '-- Select Client (Optional) --'
                        ]); 
                        ?>
                    </div>
                </div>

                <div class="row">
                    <!-- Start Date -->
                    <div class="col-md-6">
                        <?php echo Input::create([
                            'name' => 'start_date',
                            'type' => 'date',
                            'label' => 'Start Date',
                            'value' => $formData['start_date'] ?? '',
                            'icon' => 'fas fa-calendar'
                        ]); ?>
                    </div>
                    
                    <!-- End Date -->
                    <div class="col-md-6">
                        <?php echo Input::create([
                            'name' => 'end_date',
                            'type' => 'date',
                            'label' => 'End Date',
                            'value' => $formData['end_date'] ?? '',
                            'icon' => 'fas fa-calendar-check'
                        ]); ?>
                    </div>
                </div>

                <div class="row">
                    <!-- Estimated Hours -->
                    <div class="col-md-6">
                        <?php echo Input::create([
                            'name' => 'estimated_hours',
                            'type' => 'number',
                            'label' => 'Estimated Hours',
                            'placeholder' => '0',
                            'value' => $formData['estimated_hours'] ?? '',
                            'min' => '0',
                            'step' => '0.5',
                            'icon' => 'fas fa-clock'
                        ]); ?>
                    </div>
                    
                    <!-- Budget -->
                    <div class="col-md-6">
                        <?php echo Input::create([
                            'name' => 'budget',
                            'type' => 'number',
                            'label' => 'Budget ($)',
                            'placeholder' => '0.00',
                            'value' => $formData['budget'] ?? '',
                            'min' => '0',
                            'step' => '0.01',
                            'icon' => 'fas fa-dollar-sign'
                        ]); ?>
                    </div>
                </div>

                <div class="row">
                    <!-- Status -->
                    <div class="col-md-6">
                        <?php echo Select::create([
                            'name' => 'status',
                            'label' => 'Status',
                            'options' => [
                                'planning' => 'Planning',
                                'active' => 'Active',
                                'on_hold' => 'On Hold',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled'
                            ],
                            'value' => $formData['status'] ?? 'planning',
                            'required' => true
                        ]); ?>
                    </div>
                    
                    <!-- Priority -->
                    <div class="col-md-6">
                        <?php echo Select::create([
                            'name' => 'priority',
                            'label' => 'Priority',
                            'options' => [
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'critical' => 'Critical'
                            ],
                            'value' => $formData['priority'] ?? 'medium',
                            'required' => true
                        ]); ?>
                    </div>
                </div>

                <div class="mt-4">
                    <?php echo Button::create([
                        'type' => 'submit',
                        'variant' => 'primary',
                        'icon' => 'fas fa-save'
                    ], 'Create Project'); ?>
                    
                    <?php echo Button::create([
                        'variant' => 'secondary',
                        'icon' => 'fas fa-times',
                        'onclick' => "window.location.href='index.php'"
                    ], 'Cancel'); ?>
                </div>
            </form>
            
            <?php
            $formContent = ob_get_clean();
            
            echo Card::create([
                'title' => 'Project Information',
                'icon' => 'fas fa-info-circle'
            ], $formContent);
            ?>
        </div>
        
        <!-- Sidebar with Tips -->
        <div class="col-lg-4">
            <?php
            echo Card::create([
                'title' => 'Tips',
                'headerClass' => 'bg-info text-white'
            ], '
                <ul class="mb-0">
                    <li class="mb-2">Use a clear and descriptive project name</li>
                    <li class="mb-2">Project code should be unique and easy to remember</li>
                    <li class="mb-2">Assign a manager who will oversee the project</li>
                    <li class="mb-2">Set realistic timelines and budget</li>
                    <li>You can add team members after creating the project</li>
                </ul>
            ');
            ?>
        </div>
    </div>
</div>

<?php
$pageContent = ob_get_clean();

// Render page with layout
echo PageLayout::create([
    'title' => 'Create Project',
    'auth' => $auth
], $pageContent);
