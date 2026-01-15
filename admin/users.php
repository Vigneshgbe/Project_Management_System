<?php
ob_start(); // Fix header warning

$page_title = 'User Management';
require_once '../includes/header.php';
require_once '../components/user.php';

$auth->checkAccess('admin');

$user = new User();
$users = $user->getAll();

// Calculate stats
$total_users = count($users);
$active_users = count(array_filter($users, function($u) { return $u['status'] === 'active'; }));
$admin_users = count(array_filter($users, function($u) { return $u['role'] === 'admin'; }));
$manager_users = count(array_filter($users, function($u) { return $u['role'] === 'manager'; }));
?>

<style>
    /* MODERN PROFESSIONAL DESIGN */
    
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
    
    /* CONTAINER */
    .user-management-container {
        padding: 24px;
        max-width: 1600px;
        margin: 0 auto;
        animation: fadeIn 0.4s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* PAGE HEADER */
    .page-header {
        background: white;
        padding: 32px;
        border-radius: 16px;
        margin-bottom: 32px;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border);
        position: relative;
        overflow: hidden;
    }
    
    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }
    
    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 24px;
        flex-wrap: wrap;
    }
    
    .header-left h1 {
        margin: 0 0 20px 0;
        font-weight: 700;
        font-size: 32px;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .header-left h1 i {
        color: var(--primary);
        font-size: 28px;
    }
    
    /* STATS ROW - HORIZONTAL LAYOUT */
    .stats-row {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .stat-card {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03));
        padding: 16px 24px;
        border-radius: 12px;
        border: 1px solid rgba(99, 102, 241, 0.15);
        display: flex;
        align-items: center;
        gap: 14px;
        transition: all 0.3s ease;
        flex: 1;
        min-width: 180px;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
        border-color: rgba(99, 102, 241, 0.25);
    }
    
    .stat-icon {
        width: 52px;
        height: 52px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 22px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
    }
    
    .stat-content {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    
    .stat-number {
        font-size: 28px;
        font-weight: 800;
        color: var(--primary);
        line-height: 1;
    }
    
    .stat-label {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    /* ADD USER BUTTON */
    .btn-add-user {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        padding: 14px 28px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        white-space: nowrap;
    }
    
    .btn-add-user:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
        color: white;
        text-decoration: none;
    }
    
    .btn-add-user i {
        font-size: 14px;
    }
    
    /* SUCCESS MESSAGE */
    .success-message {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.05));
        border-left: 4px solid var(--success);
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .success-message i {
        font-size: 18px;
        color: var(--success);
        flex-shrink: 0;
    }
    
    .success-message span {
        color: #065f46;
        font-weight: 600;
        font-size: 14px;
    }
    
    /* TABLE CARD */
    .table-card {
        background: white;
        border-radius: 16px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        overflow: hidden;
    }
    
    .table-wrapper {
        overflow-x: auto;
    }
    
    .table-wrapper::-webkit-scrollbar {
        height: 8px;
    }
    
    .table-wrapper::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    
    .table-wrapper::-webkit-scrollbar-thumb {
        background: var(--primary);
        border-radius: 4px;
    }
    
    /* TABLE STYLES */
    .user-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .user-table thead {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .user-table thead th {
        color: white;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        padding: 18px 16px;
        text-align: left;
        white-space: nowrap;
        border: none;
    }
    
    .user-table thead th i {
        margin-right: 6px;
        opacity: 0.8;
    }
    
    .user-table tbody tr {
        transition: all 0.2s ease;
        border-bottom: 1px solid var(--border);
    }
    
    .user-table tbody tr:last-child {
        border-bottom: none;
    }
    
    .user-table tbody tr:hover {
        background: linear-gradient(90deg, rgba(99, 102, 241, 0.05), transparent);
    }
    
    .user-table tbody td {
        padding: 16px;
        color: var(--dark);
        font-weight: 500;
        font-size: 14px;
        vertical-align: middle;
    }
    
    /* USER CELL */
    .user-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.2);
    }
    
    .user-name {
        font-weight: 700;
        color: var(--dark);
    }
    
    /* BADGES */
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 5px 12px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }
    
    .badge i {
        font-size: 10px;
    }
    
    .badge.role-admin {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .badge.role-manager {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .badge.role-user {
        background: #f1f5f9;
        color: #475569;
    }
    
    .badge.status-active {
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge.status-inactive {
        background: #f1f5f9;
        color: #475569;
    }
    
    /* DATE */
    .user-date {
        color: #64748b;
        font-weight: 600;
        white-space: nowrap;
    }
    
    /* ACTION BUTTONS */
    .action-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: nowrap;
    }
    
    .btn-action {
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        border: 2px solid;
    }
    
    .btn-action i {
        font-size: 12px;
        transition: transform 0.3s ease;
    }
    
    .btn-action.edit {
        background: white;
        color: var(--primary);
        border-color: var(--primary);
    }
    
    .btn-action.edit:hover {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        text-decoration: none;
    }
    
    .btn-action.edit:hover i {
        transform: scale(1.2);
    }
    
    .btn-action.delete {
        background: white;
        color: var(--danger);
        border-color: var(--danger);
    }
    
    .btn-action.delete:hover {
        background: linear-gradient(135deg, var(--danger), #dc2626);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
        text-decoration: none;
    }
    
    .btn-action.delete:hover i {
        transform: scale(1.2);
    }
    
    /* EMPTY STATE */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
    }
    
    .empty-state i {
        font-size: 64px;
        margin-bottom: 16px;
        opacity: 0.3;
    }
    
    .empty-state p {
        font-size: 16px;
        font-weight: 600;
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
    @media (max-width: 1200px) {
        .user-management-container {
            padding: 20px;
        }
        .page-header {
            padding: 28px;
        }
        .header-left h1 {
            font-size: 28px;
        }
    }
    
    @media (max-width: 992px) {
        .stats-row {
            gap: 16px;
        }
        .stat-card {
            min-width: 160px;
            padding: 14px 20px;
        }
        .stat-icon {
            width: 46px;
            height: 46px;
            font-size: 20px;
        }
        .stat-number {
            font-size: 24px;
        }
        .user-table thead th,
        .user-table tbody td {
            padding: 12px;
            font-size: 13px;
        }
        .user-avatar {
            width: 36px;
            height: 36px;
            font-size: 14px;
        }
    }
    
    @media (max-width: 768px) {
        .user-management-container {
            padding: 16px;
        }
        .page-header {
            padding: 24px;
            margin-bottom: 24px;
        }
        .header-content {
            flex-direction: column;
        }
        .header-left h1 {
            font-size: 24px;
        }
        .btn-add-user {
            width: 100%;
            justify-content: center;
        }
        .stats-row {
            gap: 12px;
        }
        .stat-card {
            flex: 1 1 calc(50% - 6px);
            min-width: 140px;
            padding: 12px 16px;
        }
        .stat-icon {
            width: 42px;
            height: 42px;
            font-size: 18px;
        }
        .stat-number {
            font-size: 22px;
        }
        .user-table thead th,
        .user-table tbody td {
            padding: 10px 8px;
            font-size: 12px;
        }
        .user-avatar {
            width: 32px;
            height: 32px;
            font-size: 13px;
        }
        .action-buttons {
            flex-direction: column;
        }
        .btn-action {
            padding: 6px 10px;
            font-size: 10px;
        }
    }
    
    @media (max-width: 480px) {
        .user-management-container {
            padding: 12px;
        }
        .page-header {
            padding: 20px;
        }
        .header-left h1 {
            font-size: 20px;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        .stats-row {
            flex-direction: column;
            gap: 10px;
        }
        .stat-card {
            flex: 1 1 100%;
            min-width: unset;
            padding: 12px 16px;
        }
        .stat-icon {
            width: 40px;
            height: 40px;
            font-size: 16px;
        }
        .stat-number {
            font-size: 20px;
        }
        .stat-label {
            font-size: 10px;
        }
        .badge {
            padding: 4px 8px;
            font-size: 10px;
        }
    }
</style>

<div class="user-management-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-left">
                <h1>
                    <i class="fa fa-users"></i> User Management
                </h1>
                
                <!-- STATS ROW -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-number counter" data-target="<?php echo $total_users; ?>">0</span>
                            <span class="stat-label">Total Users</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-number counter" data-target="<?php echo $active_users; ?>">0</span>
                            <span class="stat-label">Active</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa fa-shield"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-number counter" data-target="<?php echo $admin_users; ?>">0</span>
                            <span class="stat-label">Admins</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa fa-star"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-number counter" data-target="<?php echo $manager_users; ?>">0</span>
                            <span class="stat-label">Managers</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <a href="user-create.php" class="btn-add-user">
                <i class="fa fa-plus"></i> Add User
            </a>
        </div>
    </div>
    
    <!-- SUCCESS MESSAGE -->
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="success-message">
        <i class="fa fa-check-circle"></i>
        <span><?php echo htmlspecialchars($_SESSION['success_message']); ?></span>
    </div>
    <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <!-- TABLE CARD -->
    <div class="table-card">
        <div class="table-wrapper">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <i class="fa fa-users"></i>
                    <p>No users found</p>
                </div>
            <?php else: ?>
                <table class="user-table">
                    <thead>
                        <tr>
                            <th><i class="fa fa-user"></i> Name</th>
                            <th><i class="fa fa-at"></i> Username</th>
                            <th><i class="fa fa-envelope"></i> Email</th>
                            <th><i class="fa fa-shield"></i> Role</th>
                            <th><i class="fa fa-circle"></i> Status</th>
                            <th><i class="fa fa-calendar"></i> Created</th>
                            <th><i class="fa fa-cog"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $roleIcons = [
                            'user' => 'fa-user',
                            'manager' => 'fa-users',
                            'admin' => 'fa-shield'
                        ];
                        
                        foreach ($users as $u): 
                            $roleIcon = $roleIcons[$u['role']] ?? 'fa-user';
                        ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                                    </div>
                                    <span class="user-name">
                                        <?php echo htmlspecialchars($u['full_name']); ?>
                                    </span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <span class="badge role-<?php echo $u['role']; ?>">
                                    <i class="fa <?php echo $roleIcon; ?>"></i>
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge status-<?php echo $u['status']; ?>">
                                    <?php echo ucfirst($u['status']); ?>
                                </span>
                            </td>
                            <td class="user-date"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="user-edit.php?id=<?php echo $u['id']; ?>" class="btn-action edit">
                                        <i class="fa fa-edit"></i> Edit
                                    </a>
                                    <?php if ($u['id'] != $auth->getUserId()): ?>
                                    <a href="user-delete.php?id=<?php echo $u['id']; ?>" class="btn-action delete">
                                        <i class="fa fa-trash"></i> Delete
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // ANIMATED COUNTERS
    $('.counter').each(function() {
        const $this = $(this);
        const countTo = parseInt($this.attr('data-target'));
        
        // Skip animation for zero values
        if (countTo === 0) {
            $this.text('0');
            return;
        }
        
        $({ countNum: 0 }).animate({
            countNum: countTo
        }, {
            duration: 1200,
            easing: 'swing',
            step: function() {
                $this.text(Math.floor(this.countNum));
            },
            complete: function() {
                $this.text(this.countNum);
            }
        });
    });
    
    // TABLE ROW ANIMATION - First 10 rows for performance
    $('.user-table tbody tr').slice(0, 10).each(function(index) {
        $(this).css({
            'animation': `fadeIn 0.3s ease ${index * 0.05}s both`
        });
    });
    
    // AUTO-HIDE SUCCESS MESSAGE AFTER 5 SECONDS
    if ($('.success-message').length) {
        setTimeout(function() {
            $('.success-message').fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>