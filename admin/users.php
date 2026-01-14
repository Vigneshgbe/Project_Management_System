<?php
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
    /* MODERN PREMIUM DESIGN SYSTEM */
    
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
        --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.05);
        --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.08);
        --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.12);
        --shadow-xl: 0 30px 60px rgba(0, 0, 0, 0.15);
    }
    
    /* CONTAINER */
    .user-management-container {
        padding: 32px;
        max-width: 1600px;
        margin: 0 auto;
        animation: pageLoad 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    
    @keyframes pageLoad {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* HEADER SECTION */
    .user-management-header {
        background: linear-gradient(135deg, 
            rgba(255, 255, 255, 0.95) 0%, 
            rgba(248, 250, 252, 0.95) 100%);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        padding: 40px 48px;
        margin-bottom: 40px;
        box-shadow: var(--shadow-lg);
        border: 1px solid rgba(99, 102, 241, 0.1);
        position: relative;
        overflow: hidden;
    }
    
    .user-management-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }
    
    .user-management-header::after {
        content: '';
        position: absolute;
        top: -100%;
        right: -100%;
        width: 300%;
        height: 300%;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.08) 0%, transparent 70%);
        animation: headerRotate 30s linear infinite;
    }
    
    @keyframes headerRotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .user-management-header-content {
        position: relative;
        z-index: 2;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 32px;
        flex-wrap: wrap;
    }
    
    .header-left h1 {
        margin: 0 0 20px 0;
        font-size: 42px;
        font-weight: 900;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        display: flex;
        align-items: center;
        gap: 16px;
        letter-spacing: -1px;
    }
    
    .header-left h1 i {
        font-size: 38px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: iconBounce 2s ease-in-out infinite;
    }
    
    @keyframes iconBounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-8px); }
    }
    
    /* STATS ROW */
    .user-stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-top: 8px;
    }
    
    .user-stat-item {
        background: linear-gradient(135deg, 
            rgba(99, 102, 241, 0.08) 0%, 
            rgba(139, 92, 246, 0.05) 100%);
        padding: 18px 24px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        border: 2px solid transparent;
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        position: relative;
        overflow: hidden;
    }
    
    .user-stat-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, 
            transparent, 
            rgba(255, 255, 255, 0.4), 
            transparent);
        transition: left 0.6s ease;
    }
    
    .user-stat-item:hover {
        transform: translateY(-4px);
        border-color: rgba(99, 102, 241, 0.3);
        box-shadow: 0 8px 24px rgba(99, 102, 241, 0.2);
    }
    
    .user-stat-item:hover::before {
        left: 100%;
    }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        flex-shrink: 0;
        box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
        transition: transform 0.3s ease;
    }
    
    .user-stat-item:hover .stat-icon {
        transform: scale(1.1) rotate(5deg);
    }
    
    .stat-content {
        flex: 1;
    }
    
    .stat-label {
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 4px;
    }
    
    .stat-number {
        font-size: 28px;
        font-weight: 900;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1;
    }
    
    /* ADD USER BUTTON */
    .btn-add-user {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: 16px 32px;
        border-radius: 14px;
        font-weight: 800;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 8px 24px rgba(99, 102, 241, 0.35);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        border: none;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    
    .btn-add-user::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }
    
    .btn-add-user:hover::before {
        width: 300px;
        height: 300px;
    }
    
    .btn-add-user:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 32px rgba(99, 102, 241, 0.45);
    }
    
    .btn-add-user i {
        font-size: 18px;
        position: relative;
        z-index: 1;
        transition: transform 0.3s ease;
    }
    
    .btn-add-user:hover i {
        transform: rotate(90deg);
    }
    
    .btn-add-user span {
        position: relative;
        z-index: 1;
    }
    
    /* TABLE CARD */
    .user-table-card {
        background: white;
        border-radius: 24px;
        box-shadow: var(--shadow-lg);
        overflow: hidden;
        border: 1px solid var(--border);
        animation: tableSlideUp 0.6s ease 0.2s both;
    }
    
    @keyframes tableSlideUp {
        from { opacity: 0; transform: translateY(40px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .user-table-wrapper {
        overflow-x: auto;
        position: relative;
    }
    
    .user-table-wrapper::-webkit-scrollbar {
        height: 10px;
    }
    
    .user-table-wrapper::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }
    
    .user-table-wrapper::-webkit-scrollbar-thumb {
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        border-radius: 10px;
    }
    
    .user-table-wrapper::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(90deg, var(--secondary), var(--primary));
    }
    
    /* TABLE */
    .user-management-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .user-management-table thead {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .user-management-table thead th {
        color: white;
        font-weight: 800;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 24px 20px;
        border: none;
        white-space: nowrap;
        text-align: left;
    }
    
    .user-management-table thead th i {
        margin-right: 8px;
        opacity: 0.9;
    }
    
    .user-management-table tbody tr {
        transition: all 0.3s ease;
        background: white;
        position: relative;
    }
    
    .user-management-table tbody tr::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 1px;
        background: linear-gradient(90deg, 
            transparent,
            var(--border),
            transparent);
    }
    
    .user-management-table tbody tr:hover {
        background: linear-gradient(135deg, 
            rgba(99, 102, 241, 0.04) 0%, 
            rgba(139, 92, 246, 0.02) 100%);
        transform: scale(1.005);
        box-shadow: 0 4px 20px rgba(99, 102, 241, 0.1);
        z-index: 1;
    }
    
    .user-management-table tbody tr:last-child::after {
        display: none;
    }
    
    .user-management-table tbody td {
        padding: 20px;
        color: var(--dark);
        font-weight: 600;
        font-size: 14px;
        vertical-align: middle;
    }
    
    /* USER CELL */
    .user-cell-name {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 20px;
        flex-shrink: 0;
        box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        position: relative;
        overflow: hidden;
    }
    
    .user-avatar::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, 
            transparent, 
            rgba(255, 255, 255, 0.3), 
            transparent);
        transform: rotate(45deg);
        transition: all 0.6s ease;
    }
    
    .user-management-table tbody tr:hover .user-avatar {
        transform: scale(1.15) rotate(-5deg);
        box-shadow: 0 6px 24px rgba(99, 102, 241, 0.5);
    }
    
    .user-management-table tbody tr:hover .user-avatar::before {
        left: 100%;
    }
    
    .user-name-text {
        font-weight: 700;
        color: var(--dark);
        font-size: 15px;
    }
    
    /* BADGES */
    .user-role-badge,
    .user-status-badge {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        white-space: nowrap;
        border: 2px solid;
        transition: all 0.3s ease;
    }
    
    /* Role Badges */
    .user-role-badge.admin {
        background: linear-gradient(135deg, 
            rgba(239, 68, 68, 0.12) 0%, 
            rgba(220, 38, 38, 0.08) 100%);
        color: #dc2626;
        border-color: rgba(220, 38, 38, 0.3);
    }
    
    .user-role-badge.admin:hover {
        background: var(--danger);
        color: white;
        border-color: var(--danger);
        transform: scale(1.05);
    }
    
    .user-role-badge.manager {
        background: linear-gradient(135deg, 
            rgba(251, 191, 36, 0.12) 0%, 
            rgba(245, 158, 11, 0.08) 100%);
        color: #f59e0b;
        border-color: rgba(245, 158, 11, 0.3);
    }
    
    .user-role-badge.manager:hover {
        background: var(--warning);
        color: white;
        border-color: var(--warning);
        transform: scale(1.05);
    }
    
    .user-role-badge.member {
        background: linear-gradient(135deg, 
            rgba(99, 102, 241, 0.12) 0%, 
            rgba(139, 92, 246, 0.08) 100%);
        color: var(--primary);
        border-color: rgba(99, 102, 241, 0.3);
    }
    
    .user-role-badge.member:hover {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border-color: transparent;
        transform: scale(1.05);
    }
    
    /* Status Badges */
    .user-status-badge.active {
        background: linear-gradient(135deg, 
            rgba(16, 185, 129, 0.12) 0%, 
            rgba(5, 150, 105, 0.08) 100%);
        color: #059669;
        border-color: rgba(5, 150, 105, 0.3);
    }
    
    .user-status-badge.active:hover {
        background: var(--success);
        color: white;
        border-color: var(--success);
        transform: scale(1.05);
    }
    
    .user-status-badge.inactive {
        background: linear-gradient(135deg, 
            rgba(107, 114, 128, 0.12) 0%, 
            rgba(75, 85, 99, 0.08) 100%);
        color: #4b5563;
        border-color: rgba(75, 85, 99, 0.3);
    }
    
    .user-status-badge.inactive:hover {
        background: #6b7280;
        color: white;
        border-color: #6b7280;
        transform: scale(1.05);
    }
    
    /* DATE */
    .user-date {
        color: #64748b;
        font-weight: 600;
        white-space: nowrap;
        font-size: 13px;
    }
    
    /* ACTIONS */
    .user-actions {
        display: flex;
        gap: 10px;
        flex-wrap: nowrap;
    }
    
    .btn-action {
        padding: 10px 18px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        border: 2px solid;
        position: relative;
        overflow: hidden;
    }
    
    .btn-action::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: width 0.4s, height 0.4s;
    }
    
    .btn-action:hover::before {
        width: 200px;
        height: 200px;
    }
    
    .btn-action i,
    .btn-action span {
        position: relative;
        z-index: 1;
    }
    
    .btn-action.edit {
        background: white;
        color: var(--primary);
        border-color: var(--primary);
    }
    
    .btn-action.edit::before {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
    }
    
    .btn-action.edit:hover {
        color: white;
        border-color: transparent;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.35);
    }
    
    .btn-action.delete {
        background: white;
        color: var(--danger);
        border-color: var(--danger);
    }
    
    .btn-action.delete::before {
        background: linear-gradient(135deg, var(--danger), #dc2626);
    }
    
    .btn-action.delete:hover {
        color: white;
        border-color: transparent;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.35);
    }
    
    .btn-action:hover i {
        transform: scale(1.2);
    }
    
    /* RESPONSIVE DESIGN */
    @media (max-width: 1200px) {
        .user-management-container {
            padding: 24px;
        }
        .user-management-header {
            padding: 32px 36px;
        }
        .header-left h1 {
            font-size: 36px;
        }
    }
    
    @media (max-width: 992px) {
        .user-management-container {
            padding: 20px;
        }
        .user-management-header {
            padding: 28px 32px;
        }
        .header-left h1 {
            font-size: 32px;
        }
        .user-stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .user-management-container {
            padding: 16px;
        }
        .user-management-header {
            padding: 24px;
        }
        .user-management-header-content {
            flex-direction: column;
            align-items: stretch;
        }
        .header-left h1 {
            font-size: 28px;
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
        .btn-add-user {
            width: 100%;
            justify-content: center;
        }
        .user-stats-row {
            grid-template-columns: 1fr;
        }
        .user-management-table thead th {
            padding: 18px 14px;
            font-size: 11px;
        }
        .user-management-table tbody td {
            padding: 16px 14px;
            font-size: 13px;
        }
        .user-avatar {
            width: 42px;
            height: 42px;
            font-size: 18px;
        }
        .user-actions {
            flex-direction: column;
            gap: 8px;
        }
    }
    
    @media (max-width: 480px) {
        .user-management-container {
            padding: 12px;
        }
        .user-management-header {
            padding: 20px;
        }
        .header-left h1 {
            font-size: 24px;
        }
        .stat-number {
            font-size: 24px;
        }
        .user-avatar {
            width: 38px;
            height: 38px;
            font-size: 16px;
        }
        .btn-action {
            padding: 8px 14px;
            font-size: 11px;
        }
    }
</style>

<div class="user-management-container">
    <!-- HEADER -->
    <div class="user-management-header">
        <div class="user-management-header-content">
            <div class="header-left">
                <h1>
                    <i class="fa fa-users"></i>
                    <span>User Management</span>
                </h1>
                
                <!-- STATS -->
                <div class="user-stats-row">
                    <div class="user-stat-item">
                        <div class="stat-icon">
                            <i class="fa fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Total Users</div>
                            <div class="stat-number counter" data-target="<?php echo $total_users; ?>">0</div>
                        </div>
                    </div>
                    
                    <div class="user-stat-item">
                        <div class="stat-icon">
                            <i class="fa fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Active</div>
                            <div class="stat-number counter" data-target="<?php echo $active_users; ?>">0</div>
                        </div>
                    </div>
                    
                    <div class="user-stat-item">
                        <div class="stat-icon">
                            <i class="fa fa-shield"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Admins</div>
                            <div class="stat-number counter" data-target="<?php echo $admin_users; ?>">0</div>
                        </div>
                    </div>
                    
                    <div class="user-stat-item">
                        <div class="stat-icon">
                            <i class="fa fa-star"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Managers</div>
                            <div class="stat-number counter" data-target="<?php echo $manager_users; ?>">0</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <a href="user-create.php" class="btn-add-user">
                <i class="fa fa-plus"></i>
                <span>Add User</span>
            </a>
        </div>
    </div>
    
    <!-- TABLE -->
    <div class="user-table-card">
        <div class="user-table-wrapper">
            <table class="user-management-table">
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
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="user-cell-name">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                                </div>
                                <span class="user-name-text">
                                    <?php echo htmlspecialchars($u['full_name']); ?>
                                </span>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td>
                            <span class="user-role-badge <?php echo $u['role']; ?>">
                                <?php echo ucfirst($u['role']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="user-status-badge <?php echo $u['status']; ?>">
                                <?php echo ucfirst($u['status']); ?>
                            </span>
                        </td>
                        <td class="user-date"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <div class="user-actions">
                                <a href="user-edit.php?id=<?php echo $u['id']; ?>" class="btn-action edit">
                                    <i class="fa fa-edit"></i>
                                    <span>Edit</span>
                                </a>
                                <?php if ($u['id'] != $auth->getUserId()): ?>
                                <a href="user-delete.php?id=<?php echo $u['id']; ?>" class="btn-action delete delete-confirm">
                                    <i class="fa fa-trash"></i>
                                    <span>Delete</span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // ANIMATED COUNTERS
    $('.counter').each(function() {
        const $this = $(this);
        const countTo = parseInt($this.attr('data-target'));
        
        $({ countNum: 0 }).animate({
            countNum: countTo
        }, {
            duration: 2000,
            easing: 'easeOutCubic',
            step: function() {
                $this.text(Math.floor(this.countNum));
            },
            complete: function() {
                $this.text(this.countNum);
            }
        });
    });
    
    // TABLE ROW STAGGER ANIMATION
    $('.user-management-table tbody tr').each(function(index) {
        $(this).css({
            'animation': `tableSlideUp 0.4s ease ${index * 0.05}s both`
        });
    });
    
    // DELETE CONFIRMATION
    $('.delete-confirm').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            e.preventDefault();
            return false;
        }
    });
    
    // ENHANCED HOVER EFFECTS
    $('.user-management-table tbody tr').each(function() {
        const $row = $(this);
        
        $row.on('mouseenter', function() {
            $(this).find('.btn-action i').css({
                'transition': 'transform 0.3s ease'
            });
        });
    });
    
    // SMOOTH SCROLL TO TOP ON PAGE LOAD
    if (window.location.hash) {
        setTimeout(function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }, 100);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>