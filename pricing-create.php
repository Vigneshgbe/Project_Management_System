<?php
$page_title = 'Create Pricing Item';
require_once 'includes/header.php';
require_once 'components/pricing.php';

$auth->checkAccess('manager');

$project_id = $_GET['project_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pricing = new Pricing();
    
    $data = [
        'project_id' => $_POST['project_id'],
        'item_name' => $_POST['item_name'],
        'description' => $_POST['description'],
        'category' => $_POST['category'],
        'unit_price' => $_POST['unit_price'],
        'quantity' => $_POST['quantity']
    ];
    
    if ($pricing->create($data)) {
        header('Location: project-detail.php?id=' . $_POST['project_id'] . '&tab=pricing');
        exit;
    }
}
?>

<style>
    .pricing-create-container {
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
    
    .pricing-create-header {
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
    
    .pricing-create-header::before {
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
    
    .pricing-create-header h1 {
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
    
    .pricing-create-header h1 i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .pricing-create-breadcrumb {
        margin-top: 15px !important;
        position: relative !important;
        z-index: 1 !important;
    }
    
    .pricing-create-breadcrumb a {
        color: #667eea !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        transition: color 0.3s ease !important;
    }
    
    .pricing-create-breadcrumb a:hover {
        color: #764ba2 !important;
    }
    
    .pricing-create-breadcrumb span {
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
    
    .pricing-info-card {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%) !important;
        padding: 25px !important;
        border-radius: 16px !important;
        border: 2px solid rgba(102, 126, 234, 0.2) !important;
        margin-bottom: 25px !important;
        animation: pulse 2s ease-in-out infinite !important;
    }
    
    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.3); }
        50% { box-shadow: 0 0 0 8px rgba(102, 126, 234, 0); }
    }
    
    .pricing-info-card i {
        font-size: 20px !important;
        color: #667eea !important;
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
    
    .total-price-display {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        padding: 20px !important;
        border-radius: 12px !important;
        text-align: center !important;
        margin-top: 15px !important;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3) !important;
    }
    
    .total-price-display .label {
        font-size: 14px !important;
        font-weight: 600 !important;
        opacity: 0.9 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .total-price-display .amount {
        font-size: 32px !important;
        font-weight: 800 !important;
        margin-top: 5px !important;
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
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .pricing-create-container {
            padding: 15px !important;
        }
        .pricing-create-header {
            padding: 25px 30px !important;
        }
        .form-card {
            padding: 30px !important;
        }
    }
    
    @media (max-width: 768px) {
        .pricing-create-container {
            padding: 10px !important;
        }
        .pricing-create-header {
            padding: 20px !important;
            margin-bottom: 20px !important;
        }
        .pricing-create-header h1 {
            font-size: 24px !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px !important;
        }
        .form-card {
            padding: 20px !important;
        }
        .total-price-display .amount {
            font-size: 28px !important;
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
        .pricing-create-container {
            padding: 8px !important;
        }
        .pricing-create-header h1 {
            font-size: 20px !important;
        }
        .form-card {
            padding: 15px !important;
        }
        .form-control-modern {
            padding: 12px 16px !important;
            font-size: 14px !important;
        }
        .total-price-display .amount {
            font-size: 24px !important;
        }
    }
</style>

<div class="pricing-create-container container-fluid">
    <div class="pricing-create-header">
        <h1>
            <i class="fa fa-plus"></i> Create Pricing Item
        </h1>
        <div class="pricing-create-breadcrumb">
            <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=pricing">
                <i class="fa fa-tag"></i> Project Pricing
            </a>
            <span>/</span>
            <span style="color: #1e293b; font-weight: 600;">Create Item</span>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="form-card">
                <form method="POST" action="" id="pricingForm">
                    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                    
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
                                      style="padding-left: 45px;"></textarea>
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
                                   placeholder="e.g., Development, Design, Infrastructure">
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
                                           value="1"
                                           placeholder="1"
                                           required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- TOTAL PRICE DISPLAY -->
                    <div class="total-price-display">
                        <div class="label">Total Price</div>
                        <div class="amount" id="totalPrice">$0.00</div>
                    </div>
                    
                    <!-- INFO CARD -->
                    <div class="pricing-info-card">
                        <i class="fa fa-info-circle"></i>
                        <strong>Automatic Calculation:</strong>
                        <span class="info-text">Total price is calculated automatically as Unit Price × Quantity</span>
                    </div>
                    
                    <!-- ACTION BUTTONS -->
                    <div class="form-actions">
                        <button type="submit" class="btn-modern primary">
                            <i class="fa fa-save"></i> Create Pricing Item
                        </button>
                        <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=pricing" class="btn-modern secondary">
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
    // FORM ANIMATION
    $('.form-card').css({
        'animation': 'slideUp 0.5s ease both'
    });
    
    // CALCULATE TOTAL PRICE
    function calculateTotal() {
        const unitPrice = parseFloat($('#unit_price').val()) || 0;
        const quantity = parseInt($('#quantity').val()) || 1;
        const total = unitPrice * quantity;
        
        $('#totalPrice').text('$' + total.toFixed(2));
        
        // Add animation effect when value changes
        $('#totalPrice').css({
            'transform': 'scale(1.1)',
            'transition': 'transform 0.2s ease'
        });
        
        setTimeout(function() {
            $('#totalPrice').css('transform', 'scale(1)');
        }, 200);
    }
    
    // LISTEN TO INPUT CHANGES
    $('#unit_price, #quantity').on('input', calculateTotal);
    
    // INITIAL CALCULATION
    calculateTotal();
    
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
    $('#pricingForm').on('submit', function(e) {
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
});
</script>

<?php require_once 'includes/footer.php'; ?>