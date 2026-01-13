<?php
$page_title = 'Delete User';
require_once '../includes/header.php';
require_once '../components/user.php';

$auth->checkAccess('admin');

$user_id = $_GET['id'] ?? 0;
$user_obj = new User();
$user = $user_obj->getById($user_id);

// Redirect if user not found or trying to delete self
if (!$user || $user_id == $auth->getUserId()) {
    header('Location: users.php');
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    if ($user_obj->delete($user_id)) {
        $_SESSION['success_message'] = 'User deleted successfully!';
        header('Location: users.php');
        exit;
    } else {
        $error_message = 'Failed to delete user. Please try again.';
    }
}
?>

<style>
    .user-delete-container {
        background: transparent !important;
        min-height: calc(100vh - 100px) !important;
        padding: 20px !important;
        margin: 0 !important;
        animation: fadeIn 0.5s ease !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .delete-modal-card {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 24px !important;
        padding: 0 !important;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        animation: scaleIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) !important;
        max-width: 600px !important;
        width: 100% !important;
        overflow: hidden !important;
        position: relative !important;
    }
    
    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.8); }
        to { opacity: 1; transform: scale(1); }
    }
    
    .delete-modal-header {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
        padding: 35px 40px !important;
        text-align: center !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .delete-modal-header::before {
        content: '' !important;
        position: absolute !important;
        top: -50% !important;
        right: -50% !important;
        width: 200% !important;
        height: 200% !important;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%) !important;
        animation: rotate 20s linear infinite !important;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .delete-icon-wrapper {
        width: 100px !important;
        height: 100px !important;
        border-radius: 50% !important;
        background: rgba(255, 255, 255, 0.2) !important;
        backdrop-filter: blur(10px) !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin-bottom: 20px !important;
        position: relative !important;
        z-index: 1 !important;
        animation: pulse 2s ease-in-out infinite !important;
        border: 3px solid rgba(255, 255, 255, 0.3) !important;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4); }
        50% { transform: scale(1.05); box-shadow: 0 0 0 20px rgba(255, 255, 255, 0); }
    }
    
    .delete-icon-wrapper i {
        font-size: 48px !important;
        color: white !important;
    }
    
    .delete-modal-header h1 {
        margin: 0 !important;
        font-weight: 800 !important;
        font-size: 28px !important;
        position: relative !important;
        z-index: 1 !important;
    }
    
    .delete-modal-body {
        padding: 40px !important;
    }
    
    .user-info-card {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, rgba(220, 38, 38, 0.05) 100%) !important;
        border-radius: 16px !important;
        padding: 25px !important;
        margin-bottom: 30px !important;
        border: 2px solid rgba(239, 68, 68, 0.2) !important;
        text-align: center !important;
    }
    
    .user-avatar-delete {
        width: 90px !important;
        height: 90px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-weight: 800 !important;
        font-size: 36px !important;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3) !important;
        margin-bottom: 15px !important;
        border: 4px solid white !important;
    }
    
    .user-name-delete {
        font-size: 22px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        margin-bottom: 8px !important;
    }
    
    .user-details-delete {
        display: flex !important;
        flex-direction: column !important;
        gap: 8px !important;
        margin-top: 15px !important;
    }
    
    .user-detail-item {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
        color: #64748b !important;
        font-weight: 600 !important;
        font-size: 14px !important;
    }
    
    .user-detail-item i {
        color: #ef4444 !important;
    }
    
    .user-detail-item .badge-inline {
        display: inline-flex !important;
        align-items: center !important;
        padding: 4px 12px !important;
        border-radius: 12px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
    }
    
    .user-detail-item .badge-inline.admin {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.15) 100%) !important;
        color: #dc2626 !important;
    }
    
    .user-detail-item .badge-inline.manager {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.15) 0%, rgba(245, 158, 11, 0.15) 100%) !important;
        color: #f59e0b !important;
    }
    
    .user-detail-item .badge-inline.member {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%) !important;
        color: #667eea !important;
    }
    
    .warning-box {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%) !important;
        border-left: 4px solid #f59e0b !important;
        border-radius: 12px !important;
        padding: 20px !important;
        margin-bottom: 30px !important;
    }
    
    .warning-box-header {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        margin-bottom: 12px !important;
    }
    
    .warning-box-header i {
        font-size: 24px !important;
        color: #f59e0b !important;
    }
    
    .warning-box-header strong {
        font-size: 16px !important;
        font-weight: 700 !important;
        color: #92400e !important;
    }
    
    .warning-box p {
        margin: 0 0 10px 0 !important;
        color: #78350f !important;
        font-weight: 500 !important;
        line-height: 1.6 !important;
    }
    
    .warning-box ul {
        margin: 10px 0 0 20px !important;
        padding: 0 !important;
    }
    
    .warning-box li {
        color: #78350f !important;
        font-weight: 500 !important;
        margin-bottom: 6px !important;
    }
    
    .error-message {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%) !important;
        border-left: 4px solid #ef4444 !important;
        border-radius: 12px !important;
        padding: 15px 20px !important;
        margin-bottom: 20px !important;
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
    }
    
    .error-message i {
        font-size: 20px !important;
        color: #ef4444 !important;
    }
    
    .error-message span {
        color: #991b1b !important;
        font-weight: 600 !important;
    }
    
    .delete-actions {
        display: flex !important;
        gap: 15px !important;
        padding-top: 25px !important;
        border-top: 2px solid #e2e8f0 !important;
    }
    
    .btn-delete-action {
        flex: 1 !important;
        padding: 16px 32px !important;
        border-radius: 12px !important;
        font-weight: 700 !important;
        font-size: 14px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        transition: all 0.3s ease !important;
        border: none !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 10px !important;
        text-decoration: none !important;
    }
    
    .btn-delete-action.danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
        box-shadow: 0 5px 20px rgba(239, 68, 68, 0.3) !important;
    }
    
    .btn-delete-action.danger:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4) !important;
    }
    
    .btn-delete-action.cancel {
        background: white !important;
        color: #667eea !important;
        border: 2px solid #667eea !important;
    }
    
    .btn-delete-action.cancel:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%) !important;
        transform: translateY(-2px) !important;
        border-color: #764ba2 !important;
        color: #764ba2 !important;
    }
    
    /* RESPONSIVE */
    @media (max-width: 768px) {
        .user-delete-container {
            padding: 15px !important;
        }
        .delete-modal-card {
            max-width: 100% !important;
        }
        .delete-modal-header {
            padding: 30px 25px !important;
        }
        .delete-icon-wrapper {
            width: 80px !important;
            height: 80px !important;
        }
        .delete-icon-wrapper i {
            font-size: 40px !important;
        }
        .delete-modal-header h1 {
            font-size: 24px !important;
        }
        .delete-modal-body {
            padding: 30px 25px !important;
        }
        .user-avatar-delete {
            width: 75px !important;
            height: 75px !important;
            font-size: 30px !important;
        }
        .user-name-delete {
            font-size: 20px !important;
        }
        .delete-actions {
            flex-direction: column !important;
        }
    }
    
    @media (max-width: 480px) {
        .user-delete-container {
            padding: 10px !important;
        }
        .delete-modal-header {
            padding: 25px 20px !important;
        }
        .delete-modal-header h1 {
            font-size: 20px !important;
        }
        .delete-modal-body {
            padding: 25px 20px !important;
        }
        .user-info-card {
            padding: 20px !important;
        }
        .warning-box {
            padding: 15px !important;
        }
        .btn-delete-action {
            padding: 14px 24px !important;
            font-size: 13px !important;
        }
    }
</style>

<div class="user-delete-container container-fluid">
    <div class="delete-modal-card">
        <div class="delete-modal-header">
            <div class="delete-icon-wrapper">
                <i class="fa fa-exclamation-triangle"></i>
            </div>
            <h1>Confirm User Deletion</h1>
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
                        <span class="badge-inline <?php echo $user['role']; ?>">
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
                <p style="margin-top: 15px; font-weight: 700;">Are you absolutely sure you want to proceed?</p>
            </div>
            
            <form method="POST" action="" id="deleteForm">
                <div class="delete-actions">
                    <a href="users.php" class="btn-delete-action cancel">
                        <i class="fa fa-times"></i> Cancel
                    </a>
                    <button type="submit" name="confirm_delete" class="btn-delete-action danger" id="confirmDeleteBtn">
                        <i class="fa fa-trash"></i> Delete User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // DOUBLE CONFIRMATION FOR CRITICAL ACTION
    $('#deleteForm').on('submit', function(e) {
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
            $('#confirmDeleteBtn').html('<i class="fa fa-spinner fa-spin"></i> Deleting...').prop('disabled', true);
            this.submit();
        }
    });
    
    // ANIMATE CARD ENTRANCE
    $('.delete-modal-card').css({
        'animation': 'scaleIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) both'
    });
    
    // ESCAPE KEY TO CANCEL
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            window.location.href = 'users.php';
        }
    });
    
    // FOCUS ON CANCEL BUTTON BY DEFAULT (SAFER)
    $('.btn-delete-action.cancel').focus();
});
</script>

<?php require_once '../includes/footer.php'; ?>