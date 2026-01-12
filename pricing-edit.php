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
        header('Location: project-detail.php?id=' . $pricing['project_id'] . '&tab=pricing');
        exit;
    }
}
?>

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fa fa-edit"></i> Edit Pricing Item</h1>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="item_name">Item Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="item_name" name="item_name" value="<?php echo htmlspecialchars($pricing['item_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($pricing['description']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category</label>
                            <input type="text" class="form-control" id="category" name="category" value="<?php echo htmlspecialchars($pricing['category']); ?>" placeholder="e.g., Development, Design, Infrastructure">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="unit_price">Unit Price ($) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="unit_price" name="unit_price" step="0.01" min="0" value="<?php echo $pricing['unit_price']; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="quantity">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="<?php echo $pricing['quantity']; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> <strong>Current Total:</strong> $<?php echo number_format($pricing['total_price'], 2); ?>
                        </div>
                        
                        <hr>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-save"></i> Update Pricing Item
                        </button>
                        <a href="project-detail.php?id=<?php echo $pricing['project_id']; ?>&tab=pricing" class="btn btn-default btn-lg">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
