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
    .pricing-edit-container {
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
    
    .pricing-edit-header {
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
    
    .pricing-edit-header::before {
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
    
    .pricing-edit-header h1 {
        margin: 0 !important;
        font-weight: 800 !important;
        font-size: 32px !important;
        position: relative !important;
        z-index: 1 !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        display: flex !important;
        align-items: center !important;
        gap: 15px !important;
    }
    
    .pricing-edit-header h1 i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .pricing-edit-breadcrumb {
        margin-top: 15px !important;
        position: relative !important;
        z-index: 1 !important;
    }
    
    .pricing-edit-breadcrumb a {
        color: #667eea !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        transition: color 0.3s ease !important;
    }
    
    .pricing-edit-breadcrumb a:hover {
        color: #764ba2 !important;
    }
    
    .pricing-edit-breadcrumb span {
        color: #64748b !important;
        margin: 0 8px !important;
    }
    
    .form-card {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 20px !important;
        padding: 40px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        animation: slideUp 0.5s ease !important;
        margin-bottom: 25px !important;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .form-section-title {
        font-size: 18px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        margin-bottom: 25px !important;
        padding-bottom: 15px !important;
        border-bottom: 3px solid transparent !important;
        border-image: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
        border-image-slice: 1 !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
    }
    
    .form-section-title i {
        color: #667eea !important;
    }
    
    .form-group-modern {
        margin-bottom: 25px !important;
    }
    
    .form-group-modern label {
        display: block !important;
        font-weight: 700 !important;
        font-size: 14px !important;
        color: #1e293b !important;
        margin-bottom: 10px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .form-group-modern label .required {
        color: #ef4444 !important;
        margin-left: 4px !important;
    }
    
    .form-control-modern {
        width: 100% !important;
        padding: 14px 18px !important;
        border: 2px solid #e2e8f0 !important;
        border-radius: 12px !important;
        font-size: 15px !important;
        font-weight: 500 !important;
        color: #1e293b !important;
        background: white !important;
        transition: all 0.3s ease !important;
    }
    
    .form-control-modern:focus {
        outline: none !important;
        border-color: #667eea !important;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1) !important;
    }
    
    .form-control-modern:hover {
        border-color: #cbd5e1 !important;
    }
    
    .form-control-modern::placeholder {
        color: #94a3b8 !important;
    }
    
    textarea.form-control-modern {
        resize: vertical !important;
        min-height: 100px !important;
    }
    
    .input-icon-wrapper {
        position: relative !important;
    }
    
    .input-icon-wrapper i {
        position: absolute !important;
        left: 18px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        color: #667eea !important;
        font-size: 16px !important;
    }
    
    .input-icon-wrapper .form-control-modern {
        padding-left: 45px !important;
    }
    
    .input-icon-wrapper.textarea-wrapper i {
        top: 20px !important;
        transform: none !important;
    }
    
    .current-value-card {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%) !important;
        padding: 20px !important;
        border-radius: 12px !important;
        border: 2px solid rgba(102, 126, 234, 0.15) !important;
        margin-bottom: 25px !important;
    }
    
    .current-value-card .title {
        font-size: 13px !important;
        font-weight: 700 !important;
        color: #64748b !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        margin-bottom: 8px !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }
    
    .current-value-card .title i {
        color: #667eea !important;
    }
    
    .current-value-card .value {
        font-size: 24px !important;
        font-weight: 800 !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .price-comparison-card {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%) !important;
        border: 2px solid #e2e8f0 !important;
        border-radius: 16px !important;
        padding: 25px !important;
        margin-bottom: 25px !important;
    }
    
    .comparison-row {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 12px 0 !important;
        border-bottom: 1px solid #e2e8f0 !important;
    }
    
    .comparison-row:last-child {
        border-bottom: none !important;
        margin-top: 10px !important;
        padding-top: 15px !important;
        border-top: 2px dashed #cbd5e1 !important;
    }
    
    .comparison-label {
        color: #64748b !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }
    
    .comparison-label i {
        color: #667eea !important;
    }
    
    .comparison-value {
        font-weight: 700 !important;
        font-size: 15px !important;
        color: #1e293b !important;
    }
    
    .comparison-value.old {
        text-decoration: line-through !important;
        color: #94a3b8 !important;
        font-weight: 600 !important;
    }
    
    .comparison-value.new {
        color: #667eea !important;
        font-size: 18px !important;
    }
    
    .comparison-value.total {
        font-size: 24px !important;
        font-weight: 800 !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .pricing-info-card {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%) !important;
        padding: 20px !important;
        border-radius: 12px !important;
        border: 2px solid rgba(251, 191, 36, 0.2) !important;
        margin-bottom: 25px !important;
    }
    
    .pricing-info-card i {
        font-size: 18px !important;
        color: #f59e0b !important;
        margin-right: 10px !important;
    }
    
    .pricing-info-card strong {
        color: #1e293b !important;
        font-weight: 700 !important;
    }
    
    .pricing-info-card .info-text {
        color: #64748b !important;
        font-weight: 600 !important;
    }
    
    .form-actions {
        display: flex !important;
        gap: 15px !important;
        margin-top: 35px !important;
        padding-top: 30px !important;
        border-top: 2px solid #e2e8f0 !important;
        flex-wrap: wrap !important;
    }
    
    .btn-modern {
        padding: 14px 32px !important;
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
        gap: 10px !important;
        text-decoration: none !important;
    }
    
    .btn-modern.primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3) !important;
    }
    
    .btn-modern.primary:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4) !important;
    }
    
    .btn-modern.secondary {
        background: white !important;
        color: #667eea !important;
        border: 2px solid #667eea !important;
    }
    
    .btn-modern.secondary:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%) !important;
        transform: translateY(-2px) !important;
    }
    
    .changed-indicator {
        display: inline-block !important;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        color: white !important;
        padding: 4px 12px !important;
        border-radius: 20px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        margin-left: 10px !important;
        animation: pulse 2s ease-in-out infinite !important;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .pricing-edit-container {
            padding: 15px !important;
        }
        .pricing-edit-header {
            padding: 25px 30px !important;
        }
        .form-card {
            padding: 30px !important;
        }
    }
    
    @media (max-width: 768px) {
        .pricing-edit-container {
            padding: 10px !important;
        }
        .pricing-edit-header {
            padding: 20px !important;
            margin-bottom: 20px !important;
        }
        .pricing-edit-header h1 {
            font-size: 24px !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px !important;
        }
        .form-card {
            padding: 20px !important;
        }
        .comparison-row {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 8px !important;
        }
        .comparison-value.total {
            font-size: 20px !important;
        }
        .form-actions {
            flex-direction: column !important;
        }
        .btn-modern {
            width: 100% !important;
            justify-content: center !important;
        }
    }
    
    @media (max-width: 480px) {
        .pricing-edit-container {
            padding: 8px !important;
        }
        .pricing-edit-header h1 {
            font-size: 20px !important;
        }
        .form-card {
            padding: 15px !important;
        }
        .form-control-modern {
            padding: 12px 16px !important;
            font-size: 14px !important;
        }
        .input-icon-wrapper .form-control-modern {
            padding-left: 40px !important;
        }
        .current-value-card .value {
            font-size: 20px !important;
        }
        .comparison-value.total {
            font-size: 18px !important;
        }
    }
</style>

<div class="pricing-edit-container container-fluid">
    <div class="pricing-edit-header">
        <h1>
            <i class="fa fa-edit"></i> Edit Pricing Item
        </h1>
        <div class="pricing-edit-breadcrumb">
            <a href="project-detail.php?id=<?php echo $pricing['project_id']; ?>&tab=pricing">
                <i class="fa fa-tag"></i> Project Pricing
            </a>
            <span>/</span>
            <span style="color: #1e293b; font-weight: 600;">Edit Item</span>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
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
                                      style="padding-left: 45px;"><?php echo htmlspecialchars($pricing['description']); ?></textarea>
                        </div>
                    </div>
                    
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
                    
                    <!-- PRICING DETAILS -->
                    <div class="form-section-title" style="margin-top: 40px;">
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
                        <strong>Live Preview:</strong>
                        <span class="info-text">Changes to unit price or quantity will update the preview above automatically</span>
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
    </div>
</div>

<script>
$(document).ready(function() {
    // ORIGINAL VALUES
    const originalUnitPrice = parseFloat('<?php echo $pricing['unit_price']; ?>');
    const originalQuantity = parseInt('<?php echo $pricing['quantity']; ?>');
    const originalTotal = parseFloat('<?php echo $pricing['total_price']; ?>');
    
    // FORM ANIMATION
    $('.form-card').css({
        'animation': 'slideUp 0.5s ease both'
    });
    
    // UPDATE COMPARISON DISPLAY
    function updateComparison() {
        const newUnitPrice = parseFloat($('#unit_price').val()) || 0;
        const newQuantity = parseInt($('#quantity').val()) || 1;
        const newTotal = newUnitPrice * newQuantity;
        
        // Check if values changed
        const hasChanges = (newUnitPrice !== originalUnitPrice || newQuantity !== originalQuantity);
        
        if (hasChanges) {
            $('#comparisonCard').slideDown(300);
            
            // Update unit price
            if (newUnitPrice !== originalUnitPrice) {
                $('#oldUnitPrice').text('$' + originalUnitPrice.toFixed(2));
                $('#newUnitPrice').text('$' + newUnitPrice.toFixed(2));
            } else {
                $('#oldUnitPrice').parent().hide();
            }
            
            // Update quantity
            if (newQuantity !== originalQuantity) {
                $('#oldQuantity').text(originalQuantity);
                $('#newQuantity').text(newQuantity);
            } else {
                $('#oldQuantity').parent().hide();
            }
            
            // Update total
            $('#newTotalPrice').text('$' + newTotal.toFixed(2));
            
            // Animate total change
            $('#newTotalPrice').css({
                'transform': 'scale(1.1)',
                'transition': 'transform 0.2s ease'
            });
            
            setTimeout(function() {
                $('#newTotalPrice').css('transform', 'scale(1)');
            }, 200);
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
    
    // INPUT FOCUS EFFECTS
    $('.form-control-modern').on('focus', function() {
        $(this).closest('.form-group-modern').find('label').css({
            'color': '#667eea',
            'transform': 'scale(1.05)',
            'transition': 'all 0.3s ease'
        });
    }).on('blur', function() {
        $(this).closest('.form-group-modern').find('label').css({
            'color': '#1e293b',
            'transform': 'scale(1)'
        });
    });
    
    // FORM VALIDATION ENHANCEMENT
    $('#pricingEditForm').on('submit', function(e) {
        let isValid = true;
        
        $('.form-control-modern[required]').each(function() {
            if ($(this).val().trim() === '') {
                isValid = false;
                $(this).css('border-color', '#ef4444');
                $(this).on('input', function() {
                    $(this).css('border-color', '#e2e8f0');
                });
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
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