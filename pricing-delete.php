<?php
ob_start(); // Fix header warning

$page_title = 'Delete Pricing Item';
require_once 'includes/header.php';
require_once 'components/pricing.php';

$auth->checkAccess('manager');

$pricing_id = $_GET['id'] ?? 0;
$confirm = $_GET['confirm'] ?? '';

if (!$pricing_id) {
    ob_end_clean();
    header('Location: projects.php');
    exit;
}

$pricing_obj = new Pricing();
$pricing = $pricing_obj->getById($pricing_id);

if (!$pricing) {
    ob_end_clean();
    header('Location: projects.php');
    exit;
}

$project_id = $pricing['project_id'];

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    if ($pricing_obj->delete($pricing_id)) {
        ob_end_clean(); // Clear buffer before redirect
        header('Location: project-detail.php?id=' . $project_id . '&tab=pricing&deleted=1');
        exit;
    }
}
?>

<style>
    /* MODERN DRAMATIC DESIGN SYSTEM */
    
    :root {
        --danger-primary: #ef4444;
        --danger-dark: #dc2626;
        --danger-darker: #b91c1c;
        --primary: #6366f1;
        --primary-dark: #4f46e5;
        --dark: #1e293b;
        --gray-light: #f8fafc;
        --gray-medium: #64748b;
        --border: #e2e8f0;
        --shadow-dramatic: 0 25px 50px rgba(0, 0, 0, 0.25);
        --shadow-intense: 0 40px 80px rgba(239, 68, 68, 0.3);
    }
    
    /* FULL-PAGE OVERLAY */
    .pricing-delete-container {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, 
            rgba(239, 68, 68, 0.08) 0%, 
            rgba(220, 38, 38, 0.05) 50%,
            rgba(99, 102, 241, 0.03) 100%);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        z-index: 9999;
        animation: overlayFadeIn 0.4s ease;
        overflow-y: auto;
    }
    
    @keyframes overlayFadeIn {
        from { 
            opacity: 0;
            background: rgba(0, 0, 0, 0);
        }
        to { 
            opacity: 1;
        }
    }
    
    /* MODAL CARD */
    .delete-modal-card {
        background: white;
        border-radius: 24px;
        box-shadow: var(--shadow-dramatic);
        max-width: 700px;
        width: 100%;
        overflow: hidden;
        animation: modalSlideUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        position: relative;
        border: 1px solid rgba(239, 68, 68, 0.1);
    }
    
    @keyframes modalSlideUp {
        from { 
            opacity: 0;
            transform: translateY(50px) scale(0.95);
        }
        to { 
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    /* DRAMATIC HEADER */
    .delete-modal-header {
        background: linear-gradient(135deg, var(--danger-primary), var(--danger-dark));
        padding: 40px 45px;
        position: relative;
        overflow: hidden;
    }
    
    .delete-modal-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
        border-radius: 50%;
        animation: headerPulse 4s ease-in-out infinite;
    }
    
    @keyframes headerPulse {
        0%, 100% { 
            transform: scale(1) translate(0, 0);
            opacity: 0.3;
        }
        50% { 
            transform: scale(1.2) translate(10px, 10px);
            opacity: 0.5;
        }
    }
    
    .delete-modal-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: linear-gradient(90deg, 
            rgba(255, 255, 255, 0.3),
            rgba(255, 255, 255, 0.6),
            rgba(255, 255, 255, 0.3));
        animation: shimmer 3s linear infinite;
    }
    
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    
    .header-content {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .delete-icon-wrapper {
        width: 70px;
        height: 70px;
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(10px);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        color: white;
        animation: iconFloat 3s ease-in-out infinite;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        border: 3px solid rgba(255, 255, 255, 0.3);
    }
    
    @keyframes iconFloat {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-8px) rotate(5deg); }
    }
    
    .header-text h1 {
        margin: 0;
        font-size: 32px;
        font-weight: 800;
        color: white;
        text-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
        letter-spacing: -0.5px;
    }
    
    .header-text p {
        margin: 8px 0 0 0;
        font-size: 14px;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 500;
    }
    
    /* BODY */
    .delete-modal-body {
        padding: 45px;
        position: relative;
    }
    
    /* BREADCRUMB */
    .breadcrumb-wrapper {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .breadcrumb-link {
        color: var(--primary);
        text-decoration: none;
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 8px;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(99, 102, 241, 0.1));
        border: 1px solid rgba(99, 102, 241, 0.2);
    }
    
    .breadcrumb-link:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(99, 102, 241, 0.15));
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
    }
    
    .breadcrumb-link i {
        transition: transform 0.3s ease;
    }
    
    .breadcrumb-link:hover i {
        transform: translateX(-3px);
    }
    
    /* DRAMATIC WARNING */
    .warning-message {
        background: linear-gradient(135deg, 
            rgba(239, 68, 68, 0.12) 0%, 
            rgba(220, 38, 38, 0.08) 100%);
        border: 2px solid rgba(239, 68, 68, 0.3);
        border-left: 6px solid var(--danger-primary);
        padding: 24px 28px;
        border-radius: 16px;
        margin-bottom: 35px;
        position: relative;
        overflow: hidden;
        animation: warningPulse 2s ease-in-out infinite;
    }
    
    @keyframes warningPulse {
        0%, 100% { 
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
        }
        50% { 
            box-shadow: 0 0 0 8px rgba(239, 68, 68, 0);
        }
    }
    
    .warning-message::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, 
            transparent,
            var(--danger-primary),
            transparent);
        animation: warningShimmer 2s linear infinite;
    }
    
    @keyframes warningShimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    
    .warning-content {
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }
    
    .warning-icon {
        flex-shrink: 0;
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, var(--danger-primary), var(--danger-dark));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 22px;
        animation: warningIconShake 3s ease-in-out infinite;
        box-shadow: 0 4px 16px rgba(239, 68, 68, 0.3);
    }
    
    @keyframes warningIconShake {
        0%, 100% { transform: rotate(0deg); }
        10%, 30%, 50%, 70%, 90% { transform: rotate(-5deg); }
        20%, 40%, 60%, 80% { transform: rotate(5deg); }
    }
    
    .warning-text strong {
        display: block;
        color: var(--danger-dark);
        font-size: 17px;
        font-weight: 800;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .warning-text p {
        color: var(--gray-medium);
        font-size: 14px;
        font-weight: 500;
        line-height: 1.6;
        margin: 0;
    }
    
    /* PRICING DETAILS BOX */
    .pricing-details-box {
        background: linear-gradient(135deg, var(--gray-light), white);
        border: 2px solid var(--border);
        border-radius: 20px;
        padding: 32px;
        margin-bottom: 35px;
        position: relative;
        overflow: hidden;
    }
    
    .pricing-details-box::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, 
            var(--primary), 
            var(--danger-primary));
    }
    
    .pricing-details-title {
        font-size: 14px;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 24px;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 16px;
        border-bottom: 2px dashed var(--border);
    }
    
    .pricing-details-title i {
        color: var(--primary);
        font-size: 18px;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 0;
        border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        transition: all 0.3s ease;
    }
    
    .detail-row:hover {
        background: linear-gradient(90deg, 
            rgba(99, 102, 241, 0.03) 0%, 
            transparent 100%);
        padding-left: 12px;
    }
    
    .detail-row:last-child {
        border-bottom: none;
        margin-top: 12px;
        padding-top: 20px;
        border-top: 2px solid var(--border);
    }
    
    .detail-label {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--gray-medium);
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .detail-label i {
        width: 24px;
        height: 24px;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(99, 102, 241, 0.05));
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-size: 12px;
    }
    
    .detail-value {
        color: var(--dark);
        font-weight: 700;
        font-size: 15px;
    }
    
    .detail-value.price {
        color: var(--primary);
        font-size: 18px;
        font-weight: 800;
    }
    
    .detail-value.total {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-size: 28px;
        font-weight: 900;
        letter-spacing: -1px;
    }
    
    /* ACTION BUTTONS */
    .action-buttons {
        display: flex;
        gap: 16px;
        padding-top: 8px;
    }
    
    .btn-modern {
        flex: 1;
        padding: 16px 32px;
        border-radius: 14px;
        font-weight: 800;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        text-decoration: none;
        position: relative;
        overflow: hidden;
    }
    
    .btn-modern::before {
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
    
    .btn-modern:hover::before {
        width: 300px;
        height: 300px;
    }
    
    .btn-modern i {
        font-size: 16px;
        transition: transform 0.3s ease;
        position: relative;
        z-index: 1;
    }
    
    .btn-modern span {
        position: relative;
        z-index: 1;
    }
    
    .btn-modern.danger {
        background: linear-gradient(135deg, var(--danger-primary), var(--danger-dark));
        color: white;
        box-shadow: 0 8px 24px rgba(239, 68, 68, 0.35);
    }
    
    .btn-modern.danger:hover {
        background: linear-gradient(135deg, var(--danger-dark), var(--danger-darker));
        transform: translateY(-3px);
        box-shadow: 0 12px 32px rgba(239, 68, 68, 0.45);
    }
    
    .btn-modern.danger:hover i {
        transform: scale(1.2) rotate(10deg);
    }
    
    .btn-modern.secondary {
        background: white;
        color: var(--gray-medium);
        border: 2px solid var(--border);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .btn-modern.secondary:hover {
        background: var(--gray-light);
        border-color: #cbd5e1;
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }
    
    .btn-modern.secondary:hover i {
        transform: scale(1.1);
    }
    
    /* SMOOTH SCROLLBAR */
    .pricing-delete-container::-webkit-scrollbar {
        width: 10px;
    }
    
    .pricing-delete-container::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    
    .pricing-delete-container::-webkit-scrollbar-thumb {
        background: var(--danger-primary);
        border-radius: 5px;
    }
    
    .pricing-delete-container::-webkit-scrollbar-thumb:hover {
        background: var(--danger-dark);
    }
    
    /* RESPONSIVE DESIGN */
    @media (max-width: 768px) {
        .pricing-delete-container {
            padding: 15px;
            align-items: flex-start;
            padding-top: 40px;
        }
        
        .delete-modal-card {
            max-width: 100%;
        }
        
        .delete-modal-header {
            padding: 32px 28px;
        }
        
        .header-content {
            flex-direction: column;
            text-align: center;
        }
        
        .delete-icon-wrapper {
            width: 60px;
            height: 60px;
            font-size: 28px;
        }
        
        .header-text h1 {
            font-size: 26px;
        }
        
        .header-text p {
            font-size: 13px;
        }
        
        .delete-modal-body {
            padding: 32px 24px;
        }
        
        .warning-content {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .pricing-details-box {
            padding: 24px;
        }
        
        .detail-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            padding: 14px 0;
        }
        
        .detail-row:hover {
            padding-left: 0;
        }
        
        .action-buttons {
            flex-direction: column-reverse;
            gap: 12px;
        }
        
        .btn-modern {
            width: 100%;
        }
    }
    
    @media (max-width: 480px) {
        .pricing-delete-container {
            padding: 10px;
            padding-top: 30px;
        }
        
        .delete-modal-header {
            padding: 24px 20px;
        }
        
        .delete-icon-wrapper {
            width: 50px;
            height: 50px;
            font-size: 24px;
        }
        
        .header-text h1 {
            font-size: 22px;
        }
        
        .header-text p {
            font-size: 12px;
        }
        
        .delete-modal-body {
            padding: 24px 18px;
        }
        
        .warning-message {
            padding: 20px;
        }
        
        .warning-icon {
            width: 40px;
            height: 40px;
            font-size: 18px;
        }
        
        .warning-text strong {
            font-size: 15px;
        }
        
        .warning-text p {
            font-size: 13px;
        }
        
        .pricing-details-box {
            padding: 20px;
        }
        
        .pricing-details-title {
            font-size: 12px;
        }
        
        .detail-value.total {
            font-size: 24px;
        }
        
        .btn-modern {
            padding: 14px 24px;
            font-size: 12px;
        }
    }
</style>

<div class="pricing-delete-container">
    <div class="delete-modal-card">
        <!-- DRAMATIC HEADER -->
        <div class="delete-modal-header">
            <div class="header-content">
                <div class="delete-icon-wrapper">
                    <i class="fa fa-trash"></i>
                </div>
                <div class="header-text">
                    <h1>Delete Pricing Item</h1>
                    <p>Permanent removal - this action cannot be reversed</p>
                </div>
            </div>
        </div>
        
        <!-- BODY -->
        <div class="delete-modal-body">
            <!-- BREADCRUMB -->
            <div class="breadcrumb-wrapper">
                <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=pricing" class="breadcrumb-link">
                    <i class="fa fa-arrow-left"></i> Back to Project Pricing
                </a>
            </div>
            
            <!-- DRAMATIC WARNING -->
            <div class="warning-message">
                <div class="warning-content">
                    <div class="warning-icon">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                    <div class="warning-text">
                        <strong>⚠️ Critical Warning</strong>
                        <p>You are about to permanently delete this pricing item. All associated data will be removed from the project and cannot be recovered.</p>
                    </div>
                </div>
            </div>
            
            <!-- PRICING DETAILS -->
            <div class="pricing-details-box">
                <div class="pricing-details-title">
                    <i class="fa fa-info-circle"></i> Item to be Deleted
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fa fa-tag"></i> Item Name
                    </span>
                    <span class="detail-value"><?php echo htmlspecialchars($pricing['item_name']); ?></span>
                </div>
                
                <?php if ($pricing['description']): ?>
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fa fa-align-left"></i> Description
                    </span>
                    <span class="detail-value"><?php echo htmlspecialchars($pricing['description']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($pricing['category']): ?>
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fa fa-folder"></i> Category
                    </span>
                    <span class="detail-value"><?php echo htmlspecialchars($pricing['category']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fa fa-dollar"></i> Unit Price
                    </span>
                    <span class="detail-value price">$<?php echo number_format($pricing['unit_price'], 2); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fa fa-cubes"></i> Quantity
                    </span>
                    <span class="detail-value"><?php echo number_format($pricing['quantity']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fa fa-calculator"></i> Total Amount
                    </span>
                    <span class="detail-value total">$<?php echo number_format($pricing['total_price'], 2); ?></span>
                </div>
            </div>
            
            <!-- ACTION BUTTONS -->
            <form method="POST" action="" id="deleteForm">
                <div class="action-buttons">
                    <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=pricing" class="btn-modern secondary">
                        <i class="fa fa-times"></i>
                        <span>Cancel</span>
                    </a>
                    <button type="submit" name="confirm_delete" class="btn-modern danger" id="deleteBtn">
                        <i class="fa fa-trash"></i>
                        <span>Delete Permanently</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // CONFIRMATION ON DELETE
    $('#deleteForm').on('submit', function(e) {
        if (!confirm('Are you absolutely sure you want to delete this pricing item? This action is permanent and cannot be undone.')) {
            e.preventDefault();
        }
    });
    
    // ESCAPE KEY TO CANCEL
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            window.location.href = 'project-detail.php?id=<?php echo $project_id; ?>&tab=pricing';
        }
    });
    
    // FOCUS DELETE BUTTON AFTER ANIMATION
    setTimeout(function() {
        $('#deleteBtn').focus();
    }, 600);
    
    // ENHANCED BUTTON INTERACTIONS
    $('.btn-modern').on('mouseenter', function() {
        $(this).find('i').css({
            'transform': $(this).hasClass('danger') ? 'scale(1.2) rotate(10deg)' : 'scale(1.1)',
            'transition': 'transform 0.3s ease'
        });
    }).on('mouseleave', function() {
        $(this).find('i').css({
            'transform': 'scale(1) rotate(0deg)'
        });
    });
    
    // CLICK OUTSIDE MODAL TO CLOSE
    $('.pricing-delete-container').on('click', function(e) {
        if ($(e.target).hasClass('pricing-delete-container')) {
            if (confirm('Are you sure you want to cancel? No changes will be made.')) {
                window.location.href = 'project-detail.php?id=<?php echo $project_id; ?>&tab=pricing';
            }
        }
    });
    
    // PREVENT MODAL CLOSE ON CARD CLICK
    $('.delete-modal-card').on('click', function(e) {
        e.stopPropagation();
    });
    
    // ADD RIPPLE EFFECT ON BUTTON CLICK
    $('.btn-modern').on('click', function(e) {
        if ($(this).hasClass('secondary')) {
            return; // Allow immediate navigation for cancel button
        }
        
        let ripple = $('<span class="ripple"></span>');
        $(this).append(ripple);
        
        let x = e.pageX - $(this).offset().left;
        let y = e.pageY - $(this).offset().top;
        
        ripple.css({
            left: x + 'px',
            top: y + 'px'
        });
        
        setTimeout(function() {
            ripple.remove();
        }, 600);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>