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
    /* MODERN PROFESSIONAL DESIGN SYSTEM */
    
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
        --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.15);
    }
    
    /* CONTAINER */
    .user-management-container {
        padding: 24px;
        max-width: 1600px;
        margin: 0 auto;
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* PAGE HEADER */
    .page-header {
        background: white;
        padding: 40px;
        border-radius: 20px;
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
        height: 5px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }
    
    .page-header::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.08) 0%, transparent 70%);
        border-radius: 50%;
        animation: headerFloat 20s ease-in-out infinite;
    }
    
    @keyframes headerFloat {
        0%, 100% { transform: translate(0, 0) scale(1); }
        50% { transform: translate(-30px, 30px) scale(1.1); }
    }
    
    .header-content {
        position: relative;
        z-index: 2;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 24px;
        flex-wrap: wrap;
    }
    
    .header-left h1 {
        margin: 0 0 24px 0;
        font-weight: 800;
        font-size: 36px;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 16px;
        letter-spacing: -1px;
    }
    
    .header-icon {
        width: 56px;
        height: 56px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 28px;
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        animation: iconPulse 3s ease-in-out infinite;
    }
    
    @keyframes iconPulse {
        0%, 100% { transform: scale(1) rotate(0deg); }
        50% { transform: scale(1.05) rotate(5deg); }
    }
    
    /* STATS ROW */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-top: 8px;
    }
    
    .stat-card {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03));
        border: 2px solid rgba(99, 102, 241, 0.1);
        border-radius: 14px;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.1), transparent);
        transition: left 0.5s ease;
    }
    
    .stat-card:hover::before {
        left: 100%;
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.15);
        border-color: rgba(99, 102, 241, 0.3);
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
        font-size: 22px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    }
    
    .stat-content {
        flex: 1;
    }
    
    .stat-number {
        font-size: 28px;
        font-weight: 900;
        color: var(--primary);
        line-height: 1;
        margin-bottom: 4px;
        letter-spacing: -1px;
    }
    
    .stat-label {
        font-size: 12px;
        font-weight: 700;
        color: var(--dark);
        text-transform: uppercase;
        letter-spacing: 0.8px;
        opacity: 0.7;
    }
    
    /* ACTION BUTTON */
    .btn-add-user {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: 14px 32px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        background: rgba(255, 255, 255, 0.2);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }
    
    .btn-add-user:hover::before {
        width: 300px;
        height: 300px;
    }
    
    .btn-add-user:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px rgba(99, 102, 241, 0.4);
    }
    
    .btn-add-user i {
        font-size: 16px;
        transition: transform 0.3s ease;
        position: relative;
        z-index: 1;
    }
    
    .btn-add-user span {
        position: relative;
        z-index: 1;
    }
    
    .btn-add-user:hover i {
        transform: rotate(90deg);
    }
    
    /* TABLE CARD */
    .table-card {
        background: white;
        border-radius: 20px;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border);
        overflow: hidden;
        animation: slideUp 0.6s ease;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .table-wrapper {
        overflow-x: auto;
        position: relative;
    }
    
    .table-wrapper::-webkit-scrollbar {
        height: 10px;
    }
    
    .table-wrapper::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }
    
    .table-wrapper::-webkit-scrollbar-thumb {
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        border-radius: 10px;
    }
    
    /* TABLE */
    .user-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .user-table thead {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .user-table thead th {
        color: white;
        font-weight: 800;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 20px 16px;
        border: none;
        white-space: nowrap;
        text-align: left;
    }
    
    .user-table thead th:first-child {
        padding-left: 32px;
        border-top-left-radius: 20px;
    }
    
    .user-table thead th:last-child {
        padding-right: 32px;
        border-top-right-radius: 20px;
    }
    
    .user-table thead th i {
        margin-right: 6px;
        opacity: 0.9;
    }
    
    .user-table tbody tr {
        transition: all 0.3s ease;
        background: white;
        position: relative;
    }
    
    .user-table tbody tr::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 1px;
        background: var(--border);
    }
    
    .user-table tbody tr:last-child::after {
        display: none;
    }
    
    .user-table tbody tr:hover {
        background: linear-gradient(90deg, rgba(99, 102, 241, 0.03), transparent);
        transform: scale(1.002);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.08);
    }
    
    .user-table tbody td {
        padding: 20px 16px;
        color: var(--dark);
        font-weight: 500;
        font-size: 14px;
        vertical-align: middle;
    }
    
    .user-table tbody td:first-child {
        padding-left: 32px;
    }
    
    .user-table tbody td:last-child {
        padding-right: 32px;
    }
    
    /* USER CELL */
    .user-cell {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    
    .user-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 18px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        transition: all 0.3s ease;
        position: relative;
    }
    
    .user-avatar::after {
        content: '';
        position: absolute;
        inset: -4px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: -1;
        filter: blur(8px);
    }
    
    .user-table tbody tr:hover .user-avatar {
        transform: scale(1.1) rotate(5deg);
    }
    
    .user-table tbody tr:hover .user-avatar::after {
        opacity: 0.4;
    }
    
    .user-name {
        font-weight: 700;
        color: var(--dark);
        font-size: 15px;
    }
    
    /* BADGES */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        white-space: nowrap;
    }
    
    .badge.admin {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.12), rgba(220, 38, 38, 0.12));
        color: #dc2626;
        border: 2px solid rgba(239, 68, 68, 0.2);
    }
    
    .badge.manager {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.12), rgba(245, 158, 11, 0.12));
        color: #f59e0b;
        border: 2px solid rgba(251, 191, 36, 0.2);
    }
    
    .badge.member {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.12), rgba(139, 92, 246, 0.12));
        color: var(--primary);
        border: 2px solid rgba(99, 102, 241, 0.2);
    }
    
    .badge.active {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(5, 150, 105, 0.12));
        color: #059669;
        border: 2px solid rgba(16, 185, 129, 0.2);
    }
    
    .badge.inactive {
        background: linear-gradient(135deg, rgba(107, 114, 128, 0.12), rgba(75, 85, 99, 0.12));
        color: #4b5563;
        border: 2px solid rgba(107, 114, 128, 0.2);
    }
    
    /* DATE */
    .date-text {
        color: #64748b;
        font-weight: 600;
        font-size: 13px;
        white-space: nowrap;
    }
    
    /* ACTIONS */
    .actions {
        display: flex;
        gap: 8px;
        flex-wrap: nowrap;
    }
    
    .btn-action {
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid;
    }
    
    .btn-action.edit {
        background: white;
        color: var(--primary);
        border-color: var(--primary);
    }
    
    .btn-action.edit:hover {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border-color: transparent;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.3);
    }
    
    .btn-action.delete {
        background: white;
        color: var(--danger);
        border-color: var(--danger);
    }
    
    .btn-action.delete:hover {
        background: linear-gradient(135deg, var(--danger), #dc2626);
        color: white;
        border-color: transparent;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(239, 68, 68, 0.3);
    }
    
    .btn-action i {
        font-size: 12px;
    }
    
    /* EMPTY STATE */
    .empty-state {
        padding: 80px 40px;
        text-align: center;
        color: #94a3b8;
    }
    
    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.3;
    }
    
    .empty-state h3 {
        font-size: 20px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 8px;
    }
    
    .empty-state p {
        font-size: 14px;
        margin: 0;
    }
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .user-management-container {
            padding: 20px;
        }
        
        .page-header {
            padding: 32px;
        }
        
        .header-left h1 {
            font-size: 32px;
        }
    }
    
    @media (max-width: 992px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .user-table thead th,
        .user-table tbody td {
            padding: 16px 12px;
        }
        
        .user-table thead th:first-child,
        .user-table tbody td:first-child {
            padding-left: 20px;
        }
        
        .user-table thead th:last-child,
        .user-table tbody td:last-child {
            padding-right: 20px;
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
            align-items: stretch;
        }
        
        .header-left h1 {
            font-size: 28px;
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
        
        .stats-row {
            grid-template-columns: 1fr;
        }
        
        .btn-add-user {
            width: 100%;
            justify-content: center;
        }
        
        .user-table thead th {
            font-size: 10px;
            padding: 14px 8px;
        }
        
        .user-table tbody td {
            font-size: 13px;
            padding: 14px 8px;
        }
        
        .user-table thead th:first-child,
        .user-table tbody td:first-child {
            padding-left: 16px;
        }
        
        .user-table thead th:last-child,
        .user-table tbody td:last-child {
            padding-right: 16px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            font-size: 16px;
        }
        
        .actions {
            flex-direction: column;
        }
        
        .btn-action {
            font-size: 10px;
            padding: 6px 12px;
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
            font-size: 24px;
        }
        
        .header-icon {
            width: 48px;
            height: 48px;
            font-size: 24px;
        }
        
        .stat-card {
            padding: 16px 20px;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            font-size: 18px;
        }
        
        .stat-number {
            font-size: 24px;
        }
        
        .stat-label {
            font-size: 11px;
        }
        
        .user-table thead th {
            font-size: 9px;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            font-size: 14px;
        }
        
        .badge {
            font-size: 10px;
            padding: 5px 12px;
        }
    }
</style>

<div class="user-management-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-left">
                <h1>
                    <div class="header-icon">
                        <i class="fa fa-users"></i>
                    </div>
                    <span>User Management</span>
                </h1>
                
                <!-- STATS -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number counter" data-target="<?php echo $total_users; ?>">0</div>
                            <div class="stat-label">Total Users</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number counter" data-target="<?php echo $active_users; ?>">0</div>
                            <div class="stat-label">Active Users</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa fa-shield"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number counter" data-target="<?php echo $admin_users; ?>">0</div>
                            <div class="stat-label">Administrators</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa fa-star"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number counter" data-target="<?php echo $manager_users; ?>">0</div>
                            <div class="stat-label">Managers</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <a href="user-create.php" class="btn-add-user">
                <i class="fa fa-plus"></i>
                <span>Add New User</span>
            </a>
        </div>
    </div>
    
    <!-- TABLE CARD -->
    <div class="table-card">
        <div class="table-wrapper">
            <?php if (count($users) > 0): ?>
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
                    <?php foreach ($users as $u): ?>
                    <tr data-user-id="<?php echo $u['id']; ?>">
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
                            <span class="badge <?php echo $u['role']; ?>">
                                <?php echo ucfirst($u['role']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $u['status']; ?>">
                                <?php echo ucfirst($u['status']); ?>
                            </span>
                        </td>
                        <td class="date-text">
                            <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                        </td>
                        <td>
                            <div class="actions">
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
            <?php else: ?>
            <div class="empty-state">
                <i class="fa fa-users"></i>
                <h3>No Users Found</h3>
                <p>There are no users in the system yet.</p>
            </div>
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
        
        $({ countNum: 0 }).animate({
            countNum: countTo
        }, {
            duration: 1800,
            easing: 'easeOutCubic',
            step: function() {
                $this.text(Math.floor(this.countNum));
            },
            complete: function() {
                $this.text(this.countNum);
            }
        });
    });
    
    // STAGGERED ROW ANIMATION
    $('.user-table tbody tr').each(function(index) {
        $(this).css({
            'animation': `slideUp 0.4s ease ${index * 0.05}s both`
        });
    });
    
    // DELETE CONFIRMATION
    $('.btn-action.delete').on('click', function(e) {
        e.preventDefault();
        const userName = $(this).closest('tr').find('.user-name').text().trim();
        
        if (confirm(`Are you sure you want to delete ${userName}? This action cannot be undone.`)) {
            window.location.href = $(this).attr('href');
        }
    });
    
    // BUTTON HOVER EFFECTS
    $('.btn-action').on('mouseenter', function() {
        $(this).find('i').css({
            'transform': 'scale(1.2)',
            'transition': 'transform 0.3s ease'
        });
    }).on('mouseleave', function() {
        $(this).find('i').css({
            'transform': 'scale(1)'
        });
    });
    
    // ROW CLICK HIGHLIGHT
    $('.user-table tbody tr').on('click', function(e) {
        if (!$(e.target).closest('.actions').length) {
            $(this).addClass('highlight');
            setTimeout(() => {
                $(this).removeClass('highlight');
            }, 1000);
        }
    });
    
    // ADD RIPPLE EFFECT TO BUTTONS
    $('.btn-add-user, .btn-action').on('click', function(e) {
        let ripple = $('<span class="ripple"></span>');
        $(this).append(ripple);
        
        let x = e.pageX - $(this).offset().left;
        let y = e.pageY - $(this).offset().top;
        
        ripple.css({
            left: x + 'px',
            top: y + 'px',
            position: 'absolute',
            width: '0',
            height: '0',
            borderRadius: '50%',
            background: 'rgba(255, 255, 255, 0.5)',
            transform: 'translate(-50%, -50%)',
            animation: 'rippleEffect 0.6s ease-out'
        });
        
        setTimeout(function() {
            ripple.remove();
        }, 600);
    });
    
    // KEYBOARD NAVIGATION
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + K to focus search (if you add search later)
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            // Focus search input
        }
    });
});

// CSS for ripple animation
$('<style>')
    .text(`
        @keyframes rippleEffect {
            to {
                width: 500px;
                height: 500px;
                opacity: 0;
            }
        }
        
        .user-table tbody tr.highlight {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.08), transparent) !important;
        }
    `)
    .appendTo('head');
</script>

<?php require_once '../includes/footer.php'; ?>