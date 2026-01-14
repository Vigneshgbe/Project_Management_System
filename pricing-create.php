<?php
ob_start(); // Fix header warning

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
        ob_end_clean(); // Clear buffer before redirect
        header('Location: project-detail.php?id=' . $_POST['project_id'] . '&tab=pricing');
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
    
    .pricing-create-container {
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
    
    /* INFO CARD */
    .pricing-info-card {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03));
        border-left: 4px solid var(--primary);
        padding: 16px 20px;
        border-radius: 12px;
        margin-top: 20px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    
    .pricing-info-card i {
        color: var(--primary);
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
    
    /* TOTAL PRICE DISPLAY */
    .total-price-display {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        padding: 24px;
        border-radius: 12px;
        text-align: center;
        margin-top: 20px;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    }
    
    .total-price-display .label {
        font-size: 12px;
        font-weight: 700;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 8px;
    }
    
    .total-price-display .amount {
        font-size: 36px;
        font-weight: 700;
        line-height: 1;
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
        .pricing-create-container {
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
        .pricing-create-container {
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
        .total-price-display .amount {
            font-size: 28px;
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
        .pricing-create-container {
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
        .total-price-display .amount {
            font-size: 24px;
        }
        .form-section-title {
            font-size: 13px;
        }
    }
</style>

<div class="pricing-create-container">
    <div class="page-header">
        <h1>
            <i class="fa fa-plus"></i> Create Pricing Item
        </h1>
        <div class="page-breadcrumb">
            <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=pricing">
                <i class="fa fa-tag"></i> Project Pricing
            </a>
            <span>/</span>
            <span class="current">Create Item</span>
        </div>
    </div>
    
    <div class="form-card">
        <form method="POST" action="" id="pricingForm">
            <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
            
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
                                      style="padding-left: 42px;"></textarea>
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
                                   placeholder="e.g., Development, Design, Infrastructure">
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
                <div>
                    <strong>Automatic Calculation:</strong>
                    <span class="info-text">Total price is calculated automatically as Unit Price × Quantity</span>
                </div>
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

<script>
$(document).ready(function() {
    // CALCULATE TOTAL PRICE
    function calculateTotal() {
        const unitPrice = parseFloat($('#unit_price').val()) || 0;
        const quantity = parseInt($('#quantity').val()) || 1;
        const total = unitPrice * quantity;
        
        $('#totalPrice').text('$' + total.toFixed(2));
    }
    
    // LISTEN TO INPUT CHANGES
    $('#unit_price, #quantity').on('input', calculateTotal);
    
    // INITIAL CALCULATION
    calculateTotal();
    
    // FORM VALIDATION
    $('#pricingForm').on('submit', function(e) {
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
});
</script>

<?php require_once 'includes/footer.php'; ?>