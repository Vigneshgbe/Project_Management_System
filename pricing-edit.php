<?php
$page_title = 'Edit Pricing Item';
require_once 'includes/header.php';
require_once 'components/pricing.php';

$auth->checkAccess('manager');

$pricing_id = $_GET['id'] ?? 0;
$pricing_obj = new Pricing();
$pricing = $pricing_obj->getById($pricing_id);

if (!$pricing) {
    header('Location: projects.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'item_name' => $_POST['item_name'],
        'description' => $_POST['description'],
        'category' => $_POST['category'],
        'unit_price' => $_POST['unit_price'],
        'quantity' => $_POST['quantity']
    ];
    
    if ($pricing_obj->update($pricing_id, $data)) {
        header('Location: project-detail.php?id=' . $pricing['project_id'] . '&tab=pricing&updated=1');
        exit;
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
        --dark: #1e293b;
        --light: #f8fafc;
        --border: #e2e8f0;
        --shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
        --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
    }
    
    .pricing-edit-container {
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
        background: linear-gradient(90deg, var(--primary), var(--secondary));
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
        color: var(--primary);
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
    
    /* CURRENT VALUE CARD */
    .current-value-card {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03));
        padding: 20px 24px;
        border-radius: 12px;
        border: 1px solid rgba(99, 102, 241, 0.15);
        margin-bottom: 24px;
    }
    
    .current-value-card .title {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .current-value-card .title i {
        color: var(--primary);
        font-size: 14px;
    }
    
    .current-value-card .value {
        font-size: 28px;
        font-weight: 700;
        color: var(--primary);
    }
    
    /* FORM CARD */
    .form-card {
        background: white;
        border-radius: 16px;
        padding: 40px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        animation: fadeInUp 0.4s ease;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* SECTION TITLES */
    .form-section-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--border);
        display: flex;
        align-items: center;
        gap: 10px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    .form-section-title i {
        color: var(--primary);
        font-size: 16px;
    }
    
    .form-section-title:not(:first-child) {
        margin-top: 40px;
    }
    
    /* FORM GROUPS */
    .form-group-modern {
        margin-bottom: 24px;
    }
    
    .form-group-modern label {
        display: block;
        font-weight: 700;
        font-size: 11px;
        color: #64748b;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    .form-group-modern label .required {
        color: var(--danger);
        margin-left: 4px;
    }
    
    /* FORM CONTROLS */
    .form-control-modern {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid var(--border);
        border-radius: 10px;
        font-size: 15px;
        font-weight: 500;
        color: var(--dark);
        background: white;
        transition: all 0.3s ease;
    }
    
    .form-control-modern:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
    
    .form-control-modern::placeholder {
        color: #94a3b8;
    }
    
    textarea.form-control-modern {
        resize: vertical;
        min-height: 100px;
    }
    
    /* INPUT WITH ICONS */
    .input-icon-wrapper {
        position: relative;
    }
    
    .input-icon-wrapper i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--primary);
        font-size: 14px;
    }
    
    .input-icon-wrapper .form-control-modern {
        padding-left: 42px;
    }
    
    .input-icon-wrapper.textarea-wrapper i {
        top: 18px;
        transform: none;
    }
    
    /* PRICE COMPARISON CARD */
    .price-comparison-card {
        background: white;
        border: 2px solid var(--border);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }
    
    .comparison-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid var(--border);
    }
    
    .comparison-row:last-child {
        border-bottom: none;
        margin-top: 10px;
        padding-top: 15px;
        border-top: 2px dashed #cbd5e1;
    }
    
    .comparison-label {
        color: #64748b;
        font-weight: 600;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .comparison-label i {
        color: var(--primary);
        font-size: 14px;
    }
    
    .comparison-value {
        font-weight: 700;
        font-size: 14px;
        color: var(--dark);
    }
    
    .comparison-value.old {
        text-decoration: line-through;
        color: #94a3b8;
        font-weight: 600;
    }
    
    .comparison-value.new {
        color: var(--primary);
        font-size: 16px;
    }
    
    .comparison-value.total {
        font-size: 24px;
        font-weight: 700;
        color: var(--primary);
    }
    
    /* INFO CARD */
    .pricing-info-card {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.1), rgba(245, 158, 11, 0.05));
        border-left: 4px solid var(--warning);
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    
    .pricing-info-card i {
        color: var(--warning);
        font-size: 18px;
        flex-shrink: 0;
        margin-top: 2px;
    }
    
    .pricing-info-card strong {
        color: var(--dark);
        font-weight: 700;
        font-size: 13px;
    }
    
    .pricing-info-card .info-text {
        color: #64748b;
        font-weight: 500;
        font-size: 13px;
    }
    
    /* CHANGED INDICATOR */
    .changed-indicator {
        display: inline-block;
        background: var(--success);
        color: white;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-left: 8px;
    }
    
    /* FORM ACTIONS */
    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 2px solid var(--border);
        flex-wrap: wrap;
    }
    
    /* BUTTONS */
    .btn-modern {
        padding: 12px 28px;
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
        gap: 8px;
        text-decoration: none;
    }
    
    .btn-modern.primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    }
    
    .btn-modern.primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
    }
    
    .btn-modern.secondary {
        background: white;
        color: var(--primary);
        border: 2px solid var(--primary);
    }
    
    .btn-modern.secondary:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
        transform: translateY(-2px);
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
        .pricing-edit-container {
            padding: 20px;
        }
        .page-header {
            padding: 28px;
        }
        .page-header h1 {
            font-size: 28px;
        }
        .form-card {
            padding: 32px;
        }
    }
    
    @media (max-width: 992px) {
        .form-card {
            padding: 28px;
        }
    }
    
    @media (max-width: 768px) {
        .pricing-edit-container {
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
        .form-card {
            padding: 24px;
        }
        .comparison-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        .comparison-value.total {
            font-size: 20px;
        }
        .form-actions {
            flex-direction: column;
        }
        .btn-modern {
            width: 100%;
            justify-content: center;
        }
    }
    
    @media (max-width: 480px) {
        .pricing-edit-container {
            padding: 12px;
        }
        .page-header {
            padding: 20px;
        }
        .page-header h1 {
            font-size: 20px;
        }
        .form-card {
            padding: 20px;
        }
        .form-control-modern {
            padding: 12px 14px;
            font-size: 14px;
        }
        .input-icon-wrapper .form-control-modern {
            padding-left: 38px;
        }
        .current-value-card .value {
            font-size: 24px;
        }
        .comparison-value.total {
            font-size: 18px;
        }
        .form-section-title {
            font-size: 13px;
        }
    }
</style>

<div class="pricing-edit-container">
    <div class="page-header">
        <h1>
            <i class="fa fa-edit"></i> Edit Pricing Item
        </h1>
        <div class="page-breadcrumb">
            <a href="project-detail.php?id=<?php echo $pricing['project_id']; ?>&tab=pricing">
                <i class="fa fa-tag"></i> Project Pricing
            </a>
            <span>/</span>
            <span class="current">Edit Item</span>
        </div>
    </div>
    
    <!-- CURRENT VALUE DISPLAY -->
    <div class="current-value-card">
        <div class="title">
            <i class="fa fa-calculator"></i> Current Total Price
        </div>
        <div class="value">$<?php echo number_format($pricing['total_price'], 2); ?></div>
    </div>
    
    <div class="form-card">
        <form method="POST" action="" id="pricingEditForm">
            <!-- ITEM DETAILS -->
            <div class="form-section-title">
                <i class="fa fa-file-text"></i> Item Details
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group-modern">
                        <label for="item_name">
                            Item Name <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="fa fa-tag"></i>
                            <input type="text" 
                                   class="form-control-modern" 
                                   id="item_name" 
                                   name="item_name" 
                                   placeholder="Enter item name"
                                   value="<?php echo htmlspecialchars($pricing['item_name']); ?>"
                                   data-original="<?php echo htmlspecialchars($pricing['item_name']); ?>"
                                   required>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group-modern">
                        <label for="description">
                            Description
                        </label>
                        <div class="input-icon-wrapper textarea-wrapper">
                            <i class="fa fa-align-left"></i>
                            <textarea class="form-control-modern" 
                                      id="description" 
                                      name="description" 
                                      rows="4"
                                      placeholder="Enter item description"
                                      data-original="<?php echo htmlspecialchars($pricing['description']); ?>"
                                      style="padding-left: 42px;"><?php echo htmlspecialchars($pricing['description']); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group-modern">
                        <label for="category">
                            Category
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="fa fa-folder"></i>
                            <input type="text" 
                                   class="form-control-modern" 
                                   id="category" 
                                   name="category" 
                                   placeholder="e.g., Development, Design, Infrastructure"
                                   value="<?php echo htmlspecialchars($pricing['category']); ?>"
                                   data-original="<?php echo htmlspecialchars($pricing['category']); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- PRICING DETAILS -->
            <div class="form-section-title">
                <i class="fa fa-dollar"></i> Pricing Details
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group-modern">
                        <label for="unit_price">
                            Unit Price ($) <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="fa fa-dollar"></i>
                            <input type="number" 
                                   class="form-control-modern" 
                                   id="unit_price" 
                                   name="unit_price" 
                                   step="0.01" 
                                   min="0"
                                   placeholder="0.00"
                                   value="<?php echo $pricing['unit_price']; ?>"
                                   data-original="<?php echo $pricing['unit_price']; ?>"
                                   required>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group-modern">
                        <label for="quantity">
                            Quantity <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="fa fa-cubes"></i>
                            <input type="number" 
                                   class="form-control-modern" 
                                   id="quantity" 
                                   name="quantity" 
                                   min="1"
                                   placeholder="1"
                                   value="<?php echo $pricing['quantity']; ?>"
                                   data-original="<?php echo $pricing['quantity']; ?>"
                                   required>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- PRICE COMPARISON -->
            <div class="price-comparison-card" id="comparisonCard" style="display: none;">
                <div class="comparison-row">
                    <span class="comparison-label">
                        <i class="fa fa-dollar"></i> Unit Price
                    </span>
                    <div>
                        <span class="comparison-value old" id="oldUnitPrice">$<?php echo number_format($pricing['unit_price'], 2); ?></span>
                        <span style="margin: 0 8px; color: #94a3b8;">→</span>
                        <span class="comparison-value new" id="newUnitPrice">$<?php echo number_format($pricing['unit_price'], 2); ?></span>
                    </div>
                </div>
                <div class="comparison-row">
                    <span class="comparison-label">
                        <i class="fa fa-cubes"></i> Quantity
                    </span>
                    <div>
                        <span class="comparison-value old" id="oldQuantity"><?php echo number_format($pricing['quantity']); ?></span>
                        <span style="margin: 0 8px; color: #94a3b8;">→</span>
                        <span class="comparison-value new" id="newQuantity"><?php echo number_format($pricing['quantity']); ?></span>
                    </div>
                </div>
                <div class="comparison-row">
                    <span class="comparison-label">
                        <i class="fa fa-calculator"></i> New Total Price
                    </span>
                    <span class="comparison-value total" id="newTotalPrice">$<?php echo number_format($pricing['total_price'], 2); ?></span>
                </div>
            </div>
            
            <!-- INFO CARD -->
            <div class="pricing-info-card">
                <i class="fa fa-info-circle"></i>
                <div>
                    <strong>Live Preview:</strong>
                    <span class="info-text">Changes to unit price or quantity will update the preview above automatically</span>
                </div>
            </div>
            
            <!-- ACTION BUTTONS -->
            <div class="form-actions">
                <button type="submit" class="btn-modern primary">
                    <i class="fa fa-save"></i> Update Pricing Item
                </button>
                <a href="project-detail.php?id=<?php echo $pricing['project_id']; ?>&tab=pricing" class="btn-modern secondary">
                    <i class="fa fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // ORIGINAL VALUES
    const originalUnitPrice = parseFloat('<?php echo $pricing['unit_price']; ?>');
    const originalQuantity = parseInt('<?php echo $pricing['quantity']; ?>');
    
    // UPDATE COMPARISON DISPLAY
    function updateComparison() {
        const newUnitPrice = parseFloat($('#unit_price').val()) || 0;
        const newQuantity = parseInt($('#quantity').val()) || 1;
        const newTotal = newUnitPrice * newQuantity;
        
        // Check if values changed
        const hasChanges = (newUnitPrice !== originalUnitPrice || newQuantity !== originalQuantity);
        
        if (hasChanges) {
            $('#comparisonCard').slideDown(300);
            
            // Update displays
            $('#newUnitPrice').text('$' + newUnitPrice.toFixed(2));
            $('#newQuantity').text(newQuantity);
            $('#newTotalPrice').text('$' + newTotal.toFixed(2));
        } else {
            $('#comparisonCard').slideUp(300);
        }
    }
    
    // LISTEN TO INPUT CHANGES
    $('#unit_price, #quantity').on('input', function() {
        updateComparison();
        
        // Add changed indicator to label
        const $label = $(this).closest('.form-group-modern').find('label');
        const originalValue = $(this).data('original');
        const currentValue = $(this).val();
        
        if (currentValue != originalValue) {
            if (!$label.find('.changed-indicator').length) {
                $label.append('<span class="changed-indicator">Modified</span>');
            }
        } else {
            $label.find('.changed-indicator').remove();
        }
    });
    
    // TRACK TEXT FIELD CHANGES
    $('#item_name, #description, #category').on('input', function() {
        const $label = $(this).closest('.form-group-modern').find('label');
        const originalValue = $(this).data('original');
        const currentValue = $(this).val();
        
        if (currentValue !== originalValue) {
            if (!$label.find('.changed-indicator').length) {
                $label.append('<span class="changed-indicator">Modified</span>');
            }
        } else {
            $label.find('.changed-indicator').remove();
        }
    });
    
    // FORM VALIDATION
    $('#pricingEditForm').on('submit', function(e) {
        let isValid = true;
        
        $('.form-control-modern[required]').each(function() {
            if ($(this).val().trim() === '') {
                isValid = false;
                $(this).css('border-color', '#ef4444');
                $(this).one('input', function() {
                    $(this).css('border-color', '#e2e8f0');
                });
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            $('html, body').animate({
                scrollTop: $('.form-control-modern[required]').filter(function() {
                    return $(this).val().trim() === '';
                }).first().offset().top - 100
            }, 300);
        }
    });
    
    // NUMBER INPUT VALIDATION
    $('#unit_price').on('input', function() {
        const val = parseFloat($(this).val());
        if (val < 0 || isNaN(val)) {
            $(this).css('border-color', '#ef4444');
        } else {
            $(this).css('border-color', '#e2e8f0');
        }
    });
    
    $('#quantity').on('input', function() {
        const val = parseInt($(this).val());
        if (val < 1 || isNaN(val)) {
            $(this).css('border-color', '#ef4444');
        } else {
            $(this).css('border-color', '#e2e8f0');
        }
    });
    
    // WARN BEFORE LEAVING IF CHANGES MADE
    let formChanged = false;
    
    $('.form-control-modern').on('input', function() {
        formChanged = true;
    });
    
    $(window).on('beforeunload', function(e) {
        if (formChanged) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
    
    $('#pricingEditForm').on('submit', function() {
        formChanged = false;
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>