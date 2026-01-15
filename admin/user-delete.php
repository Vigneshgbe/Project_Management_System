<?php
ob_start(); // Fix header warning

$page_title = 'Delete User';
require_once '../includes/header.php';
require_once '../components/user.php';

$auth->checkAccess('admin');

$user_id = $_GET['id'] ?? 0;
$user_obj = new User();
$user = $user_obj->getById($user_id);

// Redirect if user not found or trying to delete self
if (!$user || $user_id == $auth->getUserId()) {
    ob_end_clean();
    header('Location: users.php');
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    if ($user_obj->delete($user_id)) {
        ob_end_clean(); // Clear buffer before redirect
        $_SESSION['success_message'] = 'User deleted successfully!';
        header('Location: users.php');
        exit;
    } else {
        $error_message = 'Failed to delete user. Please try again.';
    }
}
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
        --danger-dark: #dc2626;
        --dark: #1e293b;
        --light: #f8fafc;
        --border: #e2e8f0;
        --shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
        --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
    }
    
    .user-delete-container {
        padding: 24px;
        max-width: 1400px;
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
        background: linear-gradient(90deg, var(--danger), var(--danger-dark));
    }
    
    .page-header h1 {
        margin: 0 0 8px 0;
        font-weight: 700;
        font-size: 32px;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .page-header h1 i {
        color: var(--danger);
        font-size: 28px;
    }
    
    .page-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        margin-top: 12px;
    }
    
    .page-breadcrumb a {
        color: var(--primary);
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .page-breadcrumb a:hover {
        color: var(--primary-dark);
    }
    
    .page-breadcrumb span {
        color: #94a3b8;
    }
    
    .page-breadcrumb .current {
        color: #64748b;
    }
    
    /* DELETE MODAL WRAPPER */
    .delete-modal-wrapper {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding: 20px 0;
    }
    
    /* DELETE MODAL CARD */
    .delete-modal-card {
        background: white;
        border-radius: 16px;
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--border);
        max-width: 700px;
        width: 100%;
        overflow: hidden;
        animation: scaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    
    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    
    /* MODAL HEADER */
    .delete-modal-header {
        background: linear-gradient(135deg, var(--danger), var(--danger-dark));
        color: white;
        padding: 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .delete-modal-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.1));
    }
    
    .delete-icon-wrapper {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
        border: 3px solid rgba(255, 255, 255, 0.25);
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .delete-icon-wrapper i {
        font-size: 40px;
        color: white;
    }
    
    .delete-modal-header h1 {
        margin: 0;
        font-weight: 700;
        font-size: 28px;
        color: white;
    }
    
    .delete-modal-header p {
        margin: 8px 0 0 0;
        opacity: 0.9;
        font-size: 14px;
        font-weight: 500;
    }
    
    /* MODAL BODY */
    .delete-modal-body {
        padding: 40px;
    }
    
    /* ERROR MESSAGE */
    .error-message {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.05));
        border-left: 4px solid var(--danger);
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
    
    .error-message i {
        font-size: 18px;
        color: var(--danger);
        flex-shrink: 0;
    }
    
    .error-message span {
        color: #991b1b;
        font-weight: 600;
        font-size: 14px;
    }
    
    /* USER INFO CARD */
    .user-info-card {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.05), rgba(220, 38, 38, 0.03));
        border: 2px solid rgba(239, 68, 68, 0.15);
        border-radius: 12px;
        padding: 32px;
        margin-bottom: 28px;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .user-info-card:hover {
        border-color: rgba(239, 68, 68, 0.25);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
    }
    
    .user-avatar-delete {
        width: 85px;
        height: 85px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 34px;
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        margin-bottom: 16px;
        border: 3px solid white;
    }
    
    .user-name-delete {
        font-size: 22px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 14px;
    }
    
    .user-details-delete {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .user-detail-item {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        color: #64748b;
        font-weight: 600;
        font-size: 14px;
    }
    
    .user-detail-item i {
        color: var(--danger);
        font-size: 13px;
        width: 16px;
        text-align: center;
    }
    
    .badge-inline {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge-inline i {
        font-size: 10px;
    }
    
    .badge-inline.admin {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .badge-inline.manager {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .badge-inline.user {
        background: #f1f5f9;
        color: #475569;
    }
    
    /* WARNING BOX */
    .warning-box {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.1), rgba(245, 158, 11, 0.05));
        border-left: 4px solid var(--warning);
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 28px;
    }
    
    .warning-box-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
    }
    
    .warning-box-header i {
        font-size: 20px;
        color: var(--warning);
    }
    
    .warning-box-header strong {
        font-size: 15px;
        font-weight: 700;
        color: #92400e;
    }
    
    .warning-box p {
        margin: 0 0 12px 0;
        color: #78350f;
        font-weight: 600;
        line-height: 1.6;
        font-size: 14px;
    }
    
    .warning-box ul {
        margin: 12px 0 0 20px;
        padding: 0;
    }
    
    .warning-box li {
        color: #78350f;
        font-weight: 500;
        margin-bottom: 8px;
        font-size: 14px;
        line-height: 1.5;
    }
    
    .warning-box .final-warning {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid rgba(251, 191, 36, 0.2);
        font-weight: 700;
        color: #92400e;
    }
    
    /* DELETE ACTIONS */
    .delete-actions {
        display: flex;
        gap: 12px;
        padding-top: 24px;
        border-top: 2px solid var(--border);
    }
    
    .btn-delete-action {
        flex: 1;
        padding: 14px 28px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
    }
    
    .btn-delete-action.danger {
        background: linear-gradient(135deg, var(--danger), var(--danger-dark));
        color: white;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
    }
    
    .btn-delete-action.danger:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(239, 68, 68, 0.35);
    }
    
    .btn-delete-action.danger:active:not(:disabled) {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
    }
    
    .btn-delete-action.danger:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    
    .btn-delete-action.cancel {
        background: white;
        color: var(--primary);
        border: 2px solid var(--primary);
    }
    
    .btn-delete-action.cancel:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
        transform: translateY(-2px);
    }
    
    .btn-delete-action.cancel:active {
        transform: translateY(0);
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
        background: var(--danger);
        border-radius: 5px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: var(--danger-dark);
    }
    
    /* RESPONSIVE DESIGN */
    @media (max-width: 1200px) {
        .user-delete-container {
            padding: 20px;
        }
        .page-header {
            padding: 28px;
        }
        .page-header h1 {
            font-size: 28px;
        }
    }
    
    @media (max-width: 768px) {
        .user-delete-container {
            padding: 16px;
        }
        .page-header {
            padding: 24px 20px;
            margin-bottom: 24px;
        }
        .page-header h1 {
            font-size: 24px;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        .page-breadcrumb {
            flex-wrap: wrap;
        }
        .delete-modal-card {
            max-width: 100%;
        }
        .delete-modal-header {
            padding: 32px 24px;
        }
        .delete-icon-wrapper {
            width: 75px;
            height: 75px;
        }
        .delete-icon-wrapper i {
            font-size: 32px;
        }
        .delete-modal-header h1 {
            font-size: 24px;
        }
        .delete-modal-body {
            padding: 32px 24px;
        }
        .user-avatar-delete {
            width: 75px;
            height: 75px;
            font-size: 30px;
        }
        .user-name-delete {
            font-size: 20px;
        }
        .delete-actions {
            flex-direction: column-reverse;
        }
    }
    
    @media (max-width: 480px) {
        .user-delete-container {
            padding: 12px;
        }
        .page-header {
            padding: 20px;
        }
        .page-header h1 {
            font-size: 20px;
        }
        .delete-modal-header {
            padding: 28px 20px;
        }
        .delete-modal-header h1 {
            font-size: 22px;
        }
        .delete-modal-body {
            padding: 28px 20px;
        }
        .user-info-card {
            padding: 28px 20px;
        }
        .user-avatar-delete {
            width: 70px;
            height: 70px;
            font-size: 28px;
        }
        .warning-box {
            padding: 16px 20px;
        }
        .btn-delete-action {
            padding: 12px 24px;
            font-size: 11px;
        }
    }
</style>

<div class="user-delete-container">
    <div class="page-header">
        <h1>
            <i class="fa fa-user-times"></i> Delete User
        </h1>
        <div class="page-breadcrumb">
            <a href="users.php">
                <i class="fa fa-users"></i> Users
            </a>
            <span>/</span>
            <span class="current">Delete User</span>
        </div>
    </div>
    
    <div class="delete-modal-wrapper">
        <div class="delete-modal-card">
            <div class="delete-modal-header">
                <div class="delete-icon-wrapper">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>
                <h1>Confirm User Deletion</h1>
                <p>This action cannot be undone</p>
            </div>
            
            <div class="delete-modal-body">
                <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <i class="fa fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="user-info-card">
                    <div class="user-avatar-delete">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div class="user-name-delete"><?php echo htmlspecialchars($user['full_name']); ?></div>
                    <div class="user-details-delete">
                        <div class="user-detail-item">
                            <i class="fa fa-at"></i>
                            <span><?php echo htmlspecialchars($user['username']); ?></span>
                        </div>
                        <div class="user-detail-item">
                            <i class="fa fa-envelope"></i>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="user-detail-item">
                            <i class="fa fa-shield"></i>
                            <?php 
                            $roleIcons = [
                                'user' => 'fa-user',
                                'manager' => 'fa-users',
                                'admin' => 'fa-shield'
                            ];
                            $roleIcon = $roleIcons[$user['role']] ?? 'fa-user';
                            ?>
                            <span class="badge-inline <?php echo $user['role']; ?>">
                                <i class="fa <?php echo $roleIcon; ?>"></i>
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="warning-box">
                    <div class="warning-box-header">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Warning: This action cannot be undone!</strong>
                    </div>
                    <p>Deleting this user will permanently remove:</p>
                    <ul>
                        <li>User account and credentials</li>
                        <li>All associated user data</li>
                        <li>Access permissions and settings</li>
                        <li>User activity history</li>
                    </ul>
                    <p class="final-warning">Are you absolutely sure you want to proceed?</p>
                </div>
                
                <form method="POST" action="" id="deleteForm">
                    <div class="delete-actions">
                        <a href="users.php" class="btn-delete-action cancel">
                            <i class="fa fa-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" name="confirm_delete" class="btn-delete-action danger" id="confirmDeleteBtn">
                            <i class="fa fa-trash"></i> Delete User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const deleteForm = $('#deleteForm');
    const confirmBtn = $('#confirmDeleteBtn');
    
    // DOUBLE CONFIRMATION FOR CRITICAL ACTION
    deleteForm.on('submit', function(e) {
        e.preventDefault();
        
        const confirmed = confirm(
            '⚠️ FINAL CONFIRMATION ⚠️\n\n' +
            'You are about to permanently delete:\n' +
            '<?php echo htmlspecialchars($user['full_name']); ?>\n\n' +
            'This action CANNOT be reversed.\n\n' +
            'Click OK to proceed with deletion or Cancel to abort.'
        );
        
        if (confirmed) {
            // Add loading state to button
            confirmBtn.html('<i class="fa fa-spinner fa-spin"></i> Deleting...').prop('disabled', true);
            this.submit();
        }
    });
    
    // ESCAPE KEY TO CANCEL
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            window.location.href = 'users.php';
        }
    });
    
    // FOCUS ON CANCEL BUTTON BY DEFAULT (SAFER)
    $('.btn-delete-action.cancel').focus();
    
    // ANIMATE ELEMENTS ON LOAD
    $('.delete-modal-card').css('animation', 'scaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both');
});
</script>

<?php require_once '../includes/footer.php'; ?>