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
    .user-management-container {
        background: transparent !important;
        min-height: calc(100vh - 100px) !important;
        padding: 20px !important;
        margin: 0 !important;
        animation: fadeIn 0.5s ease !important;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .user-management-header {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        color: #1e293b !important;
        padding: 35px 40px !important;
        border-radius: 20px !important;
        margin-bottom: 35px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        animation: slideDown 0.6s ease !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .user-management-header::before {
        content: '' !important;
        position: absolute !important;
        top: -50% !important;
        right: -50% !important;
        width: 200% !important;
        height: 200% !important;
        background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%) !important;
        animation: rotate 20s linear infinite !important;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .user-management-header-content {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        position: relative !important;
        z-index: 1 !important;
        flex-wrap: wrap !important;
        gap: 20px !important;
    }
    
    .user-management-header h1 {
        margin: 0 !important;
        font-weight: 800 !important;
        font-size: 32px !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        display: flex !important;
        align-items: center !important;
        gap: 15px !important;
    }
    
    .user-management-header h1 i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .btn-add-user {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        border: none !important;
        padding: 12px 28px !important;
        border-radius: 12px !important;
        font-weight: 700 !important;
        font-size: 14px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3) !important;
        text-decoration: none !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
    }
    
    .btn-add-user:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4) !important;
        color: white !important;
    }
    
    .btn-add-user i {
        font-size: 16px !important;
    }
    
    .user-stats-row {
        margin-top: 25px !important;
        display: flex !important;
        gap: 15px !important;
        flex-wrap: wrap !important;
    }
    
    .user-stat-item {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%) !important;
        padding: 12px 20px !important;
        border-radius: 12px !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        color: #667eea !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
    }
    
    .user-stat-item i {
        font-size: 18px !important;
    }
    
    .user-stat-item .stat-number {
        font-weight: 800 !important;
        font-size: 18px !important;
    }
    
    .user-table-card {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 20px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        overflow: hidden !important;
        animation: slideUp 0.5s ease !important;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .user-table-wrapper {
        overflow-x: auto !important;
    }
    
    .user-table-wrapper::-webkit-scrollbar {
        height: 8px !important;
    }
    
    .user-table-wrapper::-webkit-scrollbar-track {
        background: #f1f5f9 !important;
        border-radius: 10px !important;
    }
    
    .user-table-wrapper::-webkit-scrollbar-thumb {
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
        border-radius: 10px !important;
    }
    
    .user-management-table {
        width: 100% !important;
        margin: 0 !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
    }
    
    .user-management-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        position: sticky !important;
        top: 0 !important;
        z-index: 10 !important;
    }
    
    .user-management-table thead th {
        color: white !important;
        font-weight: 700 !important;
        font-size: 13px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        padding: 20px 15px !important;
        border: none !important;
        white-space: nowrap !important;
    }
    
    .user-management-table thead th:first-child {
        border-top-left-radius: 20px !important;
    }
    
    .user-management-table thead th:last-child {
        border-top-right-radius: 20px !important;
    }
    
    .user-management-table tbody tr {
        transition: all 0.3s ease !important;
        background: white !important;
    }
    
    .user-management-table tbody tr:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%) !important;
        transform: scale(1.01) !important;
        box-shadow: 0 3px 15px rgba(102, 126, 234, 0.1) !important;
    }
    
    .user-management-table tbody td {
        padding: 18px 15px !important;
        border-bottom: 1px solid #e2e8f0 !important;
        color: #1e293b !important;
        font-weight: 500 !important;
        font-size: 14px !important;
        vertical-align: middle !important;
    }
    
    .user-management-table tbody tr:last-child td {
        border-bottom: none !important;
    }
    
    .user-management-table tbody tr:last-child td:first-child {
        border-bottom-left-radius: 20px !important;
    }
    
    .user-management-table tbody tr:last-child td:last-child {
        border-bottom-right-radius: 20px !important;
    }
    
    .user-cell-name {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
    }
    
    .user-avatar {
        width: 45px !important;
        height: 45px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-weight: 800 !important;
        font-size: 18px !important;
        flex-shrink: 0 !important;
        box-shadow: 0 3px 12px rgba(102, 126, 234, 0.3) !important;
        transition: all 0.3s ease !important;
    }
    
    .user-management-table tbody tr:hover .user-avatar {
        transform: scale(1.1) rotate(5deg) !important;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.5) !important;
    }
    
    .user-name-text {
        font-weight: 700 !important;
        color: #1e293b !important;
    }
    
    .user-role-badge {
        display: inline-flex !important;
        align-items: center !important;
        padding: 6px 14px !important;
        border-radius: 20px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        white-space: nowrap !important;
    }
    
    .user-role-badge.admin {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.15) 100%) !important;
        color: #dc2626 !important;
    }
    
    .user-role-badge.manager {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.15) 0%, rgba(245, 158, 11, 0.15) 100%) !important;
        color: #f59e0b !important;
    }
    
    .user-role-badge.member {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%) !important;
        color: #667eea !important;
    }
    
    .user-status-badge {
        display: inline-flex !important;
        align-items: center !important;
        padding: 6px 14px !important;
        border-radius: 20px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        white-space: nowrap !important;
    }
    
    .user-status-badge.active {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.15) 100%) !important;
        color: #059669 !important;
    }
    
    .user-status-badge.inactive {
        background: linear-gradient(135deg, rgba(107, 114, 128, 0.15) 0%, rgba(75, 85, 99, 0.15) 100%) !important;
        color: #4b5563 !important;
    }
    
    .user-date {
        color: #64748b !important;
        font-weight: 600 !important;
        white-space: nowrap !important;
    }
    
    .user-actions {
        display: flex !important;
        gap: 8px !important;
        flex-wrap: nowrap !important;
    }
    
    .btn-action {
        padding: 8px 16px !important;
        border-radius: 8px !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        transition: all 0.3s ease !important;
        text-decoration: none !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        white-space: nowrap !important;
        border: 2px solid !important;
    }
    
    .btn-action.edit {
        background: white !important;
        color: #667eea !important;
        border-color: #667eea !important;
    }
    
    .btn-action.edit:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        border-color: transparent !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3) !important;
    }
    
    .btn-action.delete {
        background: white !important;
        color: #ef4444 !important;
        border-color: #ef4444 !important;
    }
    
    .btn-action.delete:hover {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
        border-color: transparent !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3) !important;
    }
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .user-management-container {
            padding: 15px !important;
        }
        .user-management-header {
            padding: 25px 30px !important;
        }
        .user-management-header h1 {
            font-size: 28px !important;
        }
    }
    
    @media (max-width: 992px) {
        .user-management-table thead th {
            padding: 15px 12px !important;
            font-size: 12px !important;
        }
        .user-management-table tbody td {
            padding: 15px 12px !important;
            font-size: 13px !important;
        }
        .user-avatar {
            width: 40px !important;
            height: 40px !important;
            font-size: 16px !important;
        }
    }
    
    @media (max-width: 768px) {
        .user-management-container {
            padding: 10px !important;
        }
        .user-management-header {
            padding: 20px !important;
            margin-bottom: 20px !important;
        }
        .user-management-header h1 {
            font-size: 24px !important;
        }
        .user-management-header-content {
            flex-direction: column !important;
            align-items: flex-start !important;
        }
        .btn-add-user {
            width: 100% !important;
            justify-content: center !important;
        }
        .user-stats-row {
            flex-direction: column !important;
        }
        .user-stat-item {
            width: 100% !important;
        }
        .user-management-table thead th {
            padding: 12px 8px !important;
            font-size: 11px !important;
        }
        .user-management-table tbody td {
            padding: 12px 8px !important;
            font-size: 12px !important;
        }
        .user-avatar {
            width: 35px !important;
            height: 35px !important;
            font-size: 14px !important;
        }
        .user-actions {
            flex-direction: column !important;
        }
        .btn-action {
            padding: 6px 12px !important;
            font-size: 11px !important;
        }
    }
    
    @media (max-width: 480px) {
        .user-management-container {
            padding: 8px !important;
        }
        .user-management-header h1 {
            font-size: 20px !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px !important;
        }
        .user-role-badge, .user-status-badge {
            padding: 4px 10px !important;
            font-size: 10px !important;
        }
    }
</style>

<div class="user-management-container container-fluid">
    <div class="user-management-header">
        <div class="user-management-header-content">
            <div>
                <h1>
                    <i class="fa fa-users"></i> User Management
                </h1>
                <div class="user-stats-row">
                    <div class="user-stat-item">
                        <i class="fa fa-users"></i>
                        <span><span class="stat-number counter" data-target="<?php echo $total_users; ?>">0</span> Total Users</span>
                    </div>
                    <div class="user-stat-item">
                        <i class="fa fa-check-circle"></i>
                        <span><span class="stat-number counter" data-target="<?php echo $active_users; ?>">0</span> Active</span>
                    </div>
                    <div class="user-stat-item">
                        <i class="fa fa-shield"></i>
                        <span><span class="stat-number counter" data-target="<?php echo $admin_users; ?>">0</span> Admins</span>
                    </div>
                    <div class="user-stat-item">
                        <i class="fa fa-star"></i>
                        <span><span class="stat-number counter" data-target="<?php echo $manager_users; ?>">0</span> Managers</span>
                    </div>
                </div>
            </div>
            <a href="user-create.php" class="btn-add-user">
                <i class="fa fa-plus"></i> Add User
            </a>
        </div>
    </div>
    
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
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                                <?php if ($u['id'] != $auth->getUserId()): ?>
                                <a href="user-delete.php?id=<?php echo $u['id']; ?>" class="btn-action delete delete-confirm">
                                    <i class="fa fa-trash"></i> Delete
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
            duration: 1500,
            easing: 'swing',
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
            'animation': `slideUp 0.3s ease ${index * 0.05}s both`
        });
    });
    
    // DELETE CONFIRMATION
    $('.delete-confirm').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            e.preventDefault();
            return false;
        }
    });
    
    // SEARCH FUNCTIONALITY (if you want to add it later)
    // Add a search box in header and filter table rows
});
</script>

<?php require_once '../includes/footer.php'; ?>