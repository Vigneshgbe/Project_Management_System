# Development & Customization Guide
## Project Management System (PMS)

This guide helps you understand the system architecture and customize it for your needs.

---

## Table of Contents
1. [System Architecture](#system-architecture)
2. [File Structure](#file-structure)
3. [Core Components](#core-components)
4. [Extending Functionality](#extending-functionality)
5. [Creating New Modules](#creating-new-modules)
6. [Database Operations](#database-operations)
7. [Security Best Practices](#security-best-practices)
8. [Customization Examples](#customization-examples)

---

## System Architecture

### Technology Stack
- **Backend:** PHP 7.4+ (Pure PHP, no frameworks)
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript
- **UI Framework:** Bootstrap 5
- **Icons:** Font Awesome 6

### Design Patterns Used
- **Singleton Pattern:** Database connection
- **MVC-like Structure:** Separation of logic and presentation
- **Repository Pattern:** Data access through classes

---

## File Structure

```
pms-system/
│
├── config/                      # Configuration files
│   ├── config.php              # General configuration
│   └── database.php            # Database credentials
│
├── classes/                     # Core business logic classes
│   ├── Database.php            # Database connection handler
│   ├── Auth.php                # Authentication & authorization
│   ├── User.php                # User management
│   ├── Project.php             # Project operations
│   └── Task.php                # Task operations
│
├── includes/                    # Reusable components
│   ├── header.php              # Common header
│   ├── footer.php              # Common footer
│   ├── sidebar.php             # Navigation sidebar
│   └── functions.php           # Helper functions
│
├── modules/                     # Feature modules
│   ├── auth/                   # Login, logout, register
│   ├── dashboard/              # Main dashboard
│   ├── projects/               # Project management
│   ├── tasks/                  # Task management
│   ├── users/                  # User management
│   ├── reports/                # Analytics & reports
│   └── settings/               # System settings
│
├── assets/                      # Static files
│   ├── css/                    # Custom stylesheets
│   ├── js/                     # JavaScript files
│   └── images/                 # Images and icons
│
├── uploads/                     # User uploaded files
│   ├── profiles/               # Profile pictures
│   ├── documents/              # Project documents
│   └── attachments/            # Task attachments
│
├── .htaccess                   # Apache configuration
├── index.php                   # Main entry point
├── database.sql                # Database schema
├── README.md                   # Project overview
└── INSTALLATION.md             # Installation guide
```

---

## Core Components

### 1. Database Class (`classes/Database.php`)

**Purpose:** Singleton pattern for database connection with PDO

**Key Methods:**
```php
// Get instance
$db = Database::getInstance();

// Query execution
$db->query("SELECT * FROM users WHERE user_id = :id");
$db->bind(':id', $userId);
$result = $db->single(); // Get single row
$results = $db->resultSet(); // Get multiple rows

// Transactions
$db->beginTransaction();
// ... operations
$db->commit(); // or $db->rollBack();
```

**Usage Example:**
```php
$db = Database::getInstance();
$db->query("INSERT INTO projects (project_name, manager_id) VALUES (:name, :manager)");
$db->bind(':name', $projectName);
$db->bind(':manager', $managerId);
$db->execute();
$newId = $db->lastInsertId();
```

---

### 2. Auth Class (`classes/Auth.php`)

**Purpose:** Handle authentication and authorization

**Key Methods:**
```php
$auth = new Auth();

// Login
$result = $auth->login($email, $password);

// Check login status
if ($auth->isLoggedIn()) { }

// Check role
if ($auth->hasRole('admin')) { }
if ($auth->hasRole(['admin', 'manager'])) { }

// Get current user
$userId = $auth->getUserId();
$user = $auth->getCurrentUser();

// Logout
$auth->logout();

// CSRF Protection
$token = $auth->generateCSRFToken();
$isValid = $auth->verifyCSRFToken($token);
```

---

### 3. Helper Functions (`includes/functions.php`)

**Common Functions:**

```php
// Input sanitization
$clean = sanitize($_POST['data']);

// Redirects
redirect('/modules/dashboard/index.php');

// Flash messages
setFlashMessage('Success!', 'success');
$message = getFlashMessage();

// Authentication checks
requireLogin();
requireRole('admin');

// Formatting
formatDate($date, 'Y-m-d');
formatCurrency($amount);

// File uploads
$result = uploadFile($_FILES['document']);

// Logging
logActivity($userId, 'create', 'project', $projectId, 'Created new project');

// Notifications
sendNotification($userId, 'New Task', 'You have a new task assigned');
```

---

## Extending Functionality

### Adding a New Field to Projects

**1. Update Database:**
```sql
ALTER TABLE projects ADD COLUMN budget_approved BOOLEAN DEFAULT FALSE;
```

**2. Update Project Class:**
```php
// In classes/Project.php
public function approveBudget($projectId) {
    $this->db->query("UPDATE projects SET budget_approved = TRUE WHERE project_id = :id");
    $this->db->bind(':id', $projectId);
    return $this->db->execute();
}
```

**3. Update Forms:**
```html
<!-- In project create/edit form -->
<div class="form-check">
    <input type="checkbox" name="budget_approved" id="budget_approved" 
           <?php echo ($project['budget_approved'] ?? false) ? 'checked' : ''; ?>>
    <label for="budget_approved">Budget Approved</label>
</div>
```

---

### Adding Email Notifications

**1. Install PHPMailer (if not using Hostinger's mail):**
```bash
# Via composer (if available)
composer require phpmailer/phpmailer

# Or download manually and include
```

**2. Create Email Helper Function:**
```php
// In includes/functions.php
function sendEmail($to, $subject, $message) {
    // Use mail() function (simpler for Hostinger)
    $headers = "From: " . SMTP_FROM . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}
```

**3. Use in Task Assignment:**
```php
// In Task class
public function assignTask($taskId, $userId) {
    // ... existing code ...
    
    // Send email notification
    $user = $this->userClass->getById($userId);
    $task = $this->getById($taskId);
    
    $subject = "New Task Assigned: " . $task['task_name'];
    $message = "You have been assigned a new task: " . $task['task_name'];
    
    sendEmail($user['email'], $subject, $message);
}
```

---

## Creating New Modules

### Example: Creating a "Client Portal" Module

**1. Create Module Directory:**
```
modules/
└── client-portal/
    ├── index.php
    ├── projects.php
    └── invoices.php
```

**2. Create Main Page (`modules/client-portal/index.php`):**
```php
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Restrict to clients only
requireLogin();
requireRole('client');

$auth = new Auth();
$userId = $auth->getUserId();

// Your client portal logic here
$pageTitle = 'Client Portal';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <h2>Welcome to Client Portal</h2>
    <!-- Your content here -->
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
```

**3. Add to Navigation (`includes/header.php`):**
```php
<?php if ($auth->hasRole('client')): ?>
<li>
    <a href="/modules/client-portal/index.php">
        <i class="fas fa-user-tie"></i> Client Portal
    </a>
</li>
<?php endif; ?>
```

---

## Database Operations

### Creating a New Table

**1. Write Migration:**
```sql
-- In a new file: database-updates.sql
CREATE TABLE IF NOT EXISTS milestones (
    milestone_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    milestone_name VARCHAR(200) NOT NULL,
    target_date DATE,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**2. Create Class (`classes/Milestone.php`):**
```php
<?php
class Milestone {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $this->db->query("
            INSERT INTO milestones (project_id, milestone_name, target_date) 
            VALUES (:project_id, :name, :date)
        ");
        $this->db->bind(':project_id', $data['project_id']);
        $this->db->bind(':name', $data['milestone_name']);
        $this->db->bind(':date', $data['target_date']);
        
        return $this->db->execute() ? 
            ['success' => true, 'milestone_id' => $this->db->lastInsertId()] : 
            ['success' => false];
    }
    
    public function getByProject($projectId) {
        $this->db->query("SELECT * FROM milestones WHERE project_id = :id ORDER BY target_date");
        $this->db->bind(':id', $projectId);
        return $this->db->resultSet();
    }
}
```

---

## Security Best Practices

### 1. Input Validation
```php
// Always sanitize user input
$email = sanitize($_POST['email']);

// Validate email
if (!isValidEmail($email)) {
    $error = "Invalid email format";
}

// Validate required fields
if (empty($projectName)) {
    $error = "Project name is required";
}
```

### 2. SQL Injection Prevention
```php
// NEVER do this:
$sql = "SELECT * FROM users WHERE email = '$email'"; // VULNERABLE!

// ALWAYS use prepared statements:
$this->db->query("SELECT * FROM users WHERE email = :email");
$this->db->bind(':email', $email);
```

### 3. XSS Prevention
```php
// When outputting user data:
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// Or use the sanitize function
echo sanitize($userInput);
```

### 4. CSRF Protection
```php
// In forms:
<input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">

// On form submission:
if (!validateCSRFToken($_POST['csrf_token'])) {
    die("Invalid CSRF token");
}
```

### 5. Password Security
```php
// Hashing passwords
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

// Verifying passwords
if (password_verify($password, $hash)) {
    // Password is correct
}
```

---

## Customization Examples

### Changing Color Scheme

**Update `includes/header.php`:**
```css
<style>
.sidebar {
    /* Change from purple to blue */
    background: linear-gradient(180deg, #1e3c72 0%, #2a5298 100%);
}
</style>
```

### Adding Custom Dashboard Widget

```php
<!-- In modules/dashboard/index.php -->
<div class="col-lg-4 mb-4">
    <div class="card shadow">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">Custom Widget</h6>
        </div>
        <div class="card-body">
            <!-- Your custom content -->
        </div>
    </div>
</div>
```

### Custom Report

**Create `modules/reports/custom-report.php`:**
```php
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
requireLogin();

$db = Database::getInstance();

// Custom query
$db->query("
    SELECT p.project_name, COUNT(t.task_id) as task_count
    FROM projects p
    LEFT JOIN tasks t ON p.project_id = t.project_id
    GROUP BY p.project_id
    ORDER BY task_count DESC
");

$data = $db->resultSet();

// Display results
?>
```

---

## Performance Optimization

### 1. Database Indexing
```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_project_status ON projects(status);
CREATE INDEX idx_task_assigned ON tasks(assigned_to);
CREATE INDEX idx_user_role ON users(role);
```

### 2. Query Optimization
```php
// Instead of multiple queries:
// BAD
foreach ($projects as $project) {
    $tasks = $taskClass->getByProject($project['project_id']);
}

// GOOD - Single query with JOIN
$db->query("
    SELECT p.*, COUNT(t.task_id) as task_count
    FROM projects p
    LEFT JOIN tasks t ON p.project_id = t.project_id
    GROUP BY p.project_id
");
```

### 3. Caching
```php
// Simple session-based caching
function getCachedData($key, $callback, $ttl = 300) {
    if (isset($_SESSION['cache'][$key]) && 
        $_SESSION['cache'][$key]['expires'] > time()) {
        return $_SESSION['cache'][$key]['data'];
    }
    
    $data = $callback();
    $_SESSION['cache'][$key] = [
        'data' => $data,
        'expires' => time() + $ttl
    ];
    
    return $data;
}

// Usage
$stats = getCachedData('dashboard_stats', function() use ($projectClass) {
    return $projectClass->getStatistics();
}, 600); // Cache for 10 minutes
```

---

## Testing

### Manual Testing Checklist

- [ ] User registration and login
- [ ] Password reset functionality
- [ ] Create/Edit/Delete projects
- [ ] Assign tasks to users
- [ ] File upload functionality
- [ ] Role-based access control
- [ ] Search and filter features
- [ ] Mobile responsiveness

### Database Testing
```sql
-- Test data integrity
SELECT p.project_id, p.project_name, COUNT(t.task_id)
FROM projects p
LEFT JOIN tasks t ON p.project_id = t.project_id
WHERE t.project_id IS NULL;

-- Check for orphaned records
SELECT * FROM tasks WHERE project_id NOT IN (SELECT project_id FROM projects);
```

---

## Common Customization Tasks

### 1. Add Company Logo
```php
<!-- In includes/header.php -->
<div class="sidebar-brand">
    <img src="/assets/images/logo.png" alt="Logo" style="height: 40px;">
    <?php echo APP_NAME; ?>
</div>
```

### 2. Custom Email Templates
```php
function getEmailTemplate($type, $data) {
    $templates = [
        'task_assigned' => "
            <h2>New Task Assigned</h2>
            <p>Hello {name},</p>
            <p>You have been assigned: <strong>{task}</strong></p>
            <p>Due Date: {due_date}</p>
        "
    ];
    
    $template = $templates[$type];
    foreach ($data as $key => $value) {
        $template = str_replace('{' . $key . '}', $value, $template);
    }
    
    return $template;
}
```

### 3. Export to Excel
```php
// Using PHPSpreadsheet or CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="projects.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Name', 'Status', 'Progress']);

foreach ($projects as $project) {
    fputcsv($output, [
        $project['project_id'],
        $project['project_name'],
        $project['status'],
        $project['progress']
    ]);
}

fclose($output);
```

---

## Deployment Checklist

Before going live:

- [ ] Change all default passwords
- [ ] Set DEBUG_MODE to false
- [ ] Remove database.sql from public access
- [ ] Enable HTTPS
- [ ] Configure proper file permissions
- [ ] Set up automated backups
- [ ] Test all functionality
- [ ] Review security settings
- [ ] Optimize database queries
- [ ] Enable error logging

---

## Resources

- **Bootstrap Docs:** https://getbootstrap.com/docs/
- **Font Awesome Icons:** https://fontawesome.com/icons
- **PHP Manual:** https://www.php.net/manual/
- **MySQL Documentation:** https://dev.mysql.com/doc/

---

## Need Help?

For development questions:
1. Check this documentation
2. Review the code comments
3. Test in a development environment first
4. Use browser developer tools for debugging

Happy coding! 🚀
