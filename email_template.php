<?php
// Include this at the top of your PHP file

// Enable error logging for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure upload directory exists
$upload_dir = "uploads/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    file_put_contents($upload_dir . '.htaccess', "Options +Indexes\nOrder Allow,Deny\nAllow from all");
}

// Helper function to convert a file path to base64 data URL
function getBase64Image($imagePath) {
    if (!file_exists($imagePath)) {
        error_log("Image file does not exist: " . $imagePath);
        return '';
    }
    
    $imageData = file_get_contents($imagePath);
    if ($imageData === false) {
        error_log("Failed to read image data from: " . $imagePath);
        return '';
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $imagePath);
    finfo_close($finfo);
    
    $base64 = base64_encode($imageData);
    return 'data:' . $mimeType . ';base64,' . $base64;
}

// Variable to store status messages and HTML preview
$message = '';
$preview_html = '';
$current_step = 1;

// Process the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("Form submitted. Processing file uploads...");
    
    // Determine current step based on form data
    if (isset($_POST['body_content']) && !empty($_POST['body_content'])) {
        $current_step = 2;
    }
    
    if (isset($_POST['layout_type']) && $_POST['layout_type'] !== '0') {
        $current_step = 3;
    }
    
    if (isset($_POST['regards_name']) && !empty($_POST['regards_name'])) {
        $current_step = 4;
    }
    
    // Process header image
    $header_image = '';
    $header_image_data = '';
    
    if (isset($_FILES["header_image"]) && $_FILES["header_image"]["error"] == 0) {
        $target_file = $upload_dir . basename($_FILES["header_image"]["name"]);
        error_log("Processing header image: " . $target_file);
        
        $check = getimagesize($_FILES["header_image"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["header_image"]["tmp_name"], $target_file)) {
                error_log("Header image uploaded successfully");
                $header_image = $target_file;
                $header_image_data = getBase64Image($header_image);
            } else {
                error_log("Failed to move uploaded header image: " . error_get_last()['message']);
                $message = "Failed to save header image. Please try again.";
            }
        } else {
            error_log("File is not an image");
            $message = "The uploaded header file is not a valid image.";
        }
    }
    
    // Process body content
    $body_content = isset($_POST['body_content']) ? $_POST['body_content'] : '';
    
    // Get signature and layout information
    $signature_name = isset($_POST['regards_name']) ? $_POST['regards_name'] : '';
    $signature_title = isset($_POST['regards_title']) ? $_POST['regards_title'] : '';
    $regards_text = isset($_POST['regards_text']) ? $_POST['regards_text'] : 'Best Regards,';
    $layout_type = isset($_POST['layout_type']) ? $_POST['layout_type'] : '0';
    
    // Process employee/group images based on layout type
    $employee_images = [];
    $employee_details = [];
    $group_image = '';
    $group_caption = '';
    $group_image_data = '';
    
    if ($layout_type === 'group') {
        if (isset($_FILES["group_image"]) && $_FILES["group_image"]["error"] == 0) {
            $target_file = $upload_dir . basename($_FILES["group_image"]["name"]);
            if (getimagesize($_FILES["group_image"]["tmp_name"]) !== false) {
                if (move_uploaded_file($_FILES["group_image"]["tmp_name"], $target_file)) {
                    $group_image = $target_file;
                    $group_image_data = getBase64Image($group_image);
                    $group_caption = isset($_POST["group_caption"]) ? $_POST["group_caption"] : '';
                    error_log("Group image uploaded successfully");
                }
            }
        }
    } else {
        $maxEmployees = 0;
        switch($layout_type) {
            case '1': $maxEmployees = 1; break;
            case '2': $maxEmployees = 2; break;
            case '3': $maxEmployees = 3; break;
            case '2-2': $maxEmployees = 4; break;
            case '3-2': $maxEmployees = 5; break;
            case '3-3': $maxEmployees = 9; break;
            default: $maxEmployees = 0;
        }
        
        for ($i = 1; $i <= $maxEmployees; $i++) {
            $hasDetails = isset($_POST["employee_name_" . $i]) && !empty($_POST["employee_name_" . $i]);
            
            if ($hasDetails) {
                $employee_details[] = [
                    'name' => $_POST["employee_name_" . $i],
                    'title' => isset($_POST["employee_title_" . $i]) ? $_POST["employee_title_" . $i] : ''
                ];
            } else {
                $employee_details[] = ['name' => '', 'title' => ''];
            }
            
            if (isset($_FILES["employee_image_" . $i]) && $_FILES["employee_image_" . $i]["error"] == 0) {
                $target_file = $upload_dir . basename($_FILES["employee_image_" . $i]["name"]);
                if (getimagesize($_FILES["employee_image_" . $i]["tmp_name"]) !== false) {
                    if (move_uploaded_file($_FILES["employee_image_" . $i]["tmp_name"], $target_file)) {
                        $employee_images[] = getBase64Image($target_file);
                        error_log("Employee image $i uploaded successfully");
                    } else {
                        $employee_images[] = '';
                    }
                } else {
                    $employee_images[] = '';
                }
            } else {
                $employee_images[] = '';
            }
        }
    }
    
    // Generate the email template
    if (!empty($body_content)) {
        error_log("Generating email template...");
        
        $options = [
            'header_image' => $header_image_data,
            'body_content' => $body_content,
            'signature_name' => $signature_name,
            'signature_title' => $signature_title,
            'layout_type' => $layout_type,
            'regards_text' => $regards_text
        ];
        
        if ($layout_type === 'group') {
            $options['group_image'] = $group_image_data;
            $options['group_caption'] = $group_caption;
        } else {
            $options['employee_images'] = $employee_images;
            $options['employee_details'] = $employee_details;
        }
        
        $preview_html = generateEmailTemplate($options);
        $message = "Template generated successfully!";
        error_log("Email template generated successfully");
        $current_step = 4;
    } else {
        error_log("No body content provided");
        $message = "Please provide email content.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Email Template Generator - Padak CRM</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.7.0/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        * {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
        }

        body {
            background-color: #0f172a;
            color: #e2e8f0;
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #1e293b;
        }

        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }

        /* Card styling matching the CRM theme */
        .card {
            background: #1e293b;
            border-radius: 12px;
            border: 1px solid #334155;
            transition: all 0.3s ease;
        }

        .card:hover {
            border-color: #475569;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .card-header {
            display: flex;
            align-items: center;
            padding: 1.25rem;
            border-bottom: 1px solid #334155;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Form controls */
        .form-control {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            color: #e2e8f0;
            width: 100%;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #ff6b35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #94a3b8;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Button styling */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: #ff6b35;
            color: white;
        }

        .btn-primary:hover {
            background: #ff5722;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        }

        .btn-secondary {
            background: #334155;
            color: #e2e8f0;
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        /* File upload styling */
        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: #0f172a;
            border: 2px dashed #334155;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            flex-direction: column;
        }

        .file-upload-label:hover {
            background: #1e293b;
            border-color: #ff6b35;
        }

        .file-upload input[type="file"] {
            position: absolute;
            width: 0;
            height: 0;
            opacity: 0;
        }

        .selected-file {
            margin-top: 0.75rem;
            padding: 0.75rem;
            background: #0f172a;
            border-radius: 8px;
            display: flex;
            align-items: center;
            color: #94a3b8;
            font-size: 0.875rem;
        }

        /* Progress stepper */
        .progress-stepper {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 1.25rem;
            left: 50%;
            width: 100%;
            height: 2px;
            background-color: #334155;
            z-index: 0;
        }

        .step.active:not(:last-child)::after,
        .step.completed:not(:last-child)::after {
            background-color: #ff6b35;
        }

        .step-circle {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background-color: #334155;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            position: relative;
            z-index: 1;
            font-weight: 600;
        }

        .step.active .step-circle {
            background: #ff6b35;
            color: white;
            box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.2);
        }

        .step.completed .step-circle {
            background: #10b981;
            color: white;
        }

        .step-title {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
        }

        .step.active .step-title {
            color: #ff6b35;
        }

        .step.completed .step-title {
            color: #10b981;
        }

        /* Layout options */
        .layout-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .layout-option {
            position: relative;
            transition: all 0.3s ease;
            border: 2px solid #334155;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
        }

        .layout-option:hover {
            border-color: #ff6b35;
            transform: translateY(-2px);
        }

        .layout-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .layout-option input[type="radio"]:checked + .layout-content {
            border-color: #ff6b35;
            background-color: rgba(255, 107, 53, 0.05);
        }

        .layout-option input[type="radio"]:checked + .layout-content .option-check {
            opacity: 1;
            transform: scale(1);
        }

        .layout-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem;
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 8px;
            background: #0f172a;
            height: 100%;
        }

        .layout-visual {
            background-color: #1e293b;
            width: 100%;
            height: 100px;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .option-check {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            width: 1.5rem;
            height: 1.5rem;
            background: #ff6b35;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.2s ease;
        }

        .layout-title {
            font-weight: 500;
            color: #e2e8f0;
            text-align: center;
            font-size: 0.875rem;
        }

        /* Preview section */
        .preview-section {
            background: #1e293b;
            border-radius: 12px;
            border: 1px solid #334155;
            padding: 1.5rem;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .preview-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
            background: #0f172a;
            border-radius: 12px;
            text-align: center;
            border: 2px dashed #334155;
        }

        .preview-empty i {
            font-size: 4rem;
            color: #475569;
            margin-bottom: 1.5rem;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            max-width: 350px;
            border: 1px solid;
        }

        .toast.success {
            background: #1e293b;
            border-color: #10b981;
            color: #10b981;
        }

        .toast.error {
            background: #1e293b;
            border-color: #ef4444;
            color: #ef4444;
        }

        .toast i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
        }

        /* Modal styling */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-container {
            background-color: #1e293b;
            border-radius: 12px;
            border: 1px solid #334155;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            max-width: 500px;
            width: 90%;
            padding: 2rem;
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }

        .modal-overlay.show .modal-container {
            transform: translateY(0);
        }

        /* Grid items for layout preview */
        .grid-item {
            background-color: #475569;
            border-radius: 4px;
        }

        .layout-grid {
            width: 80%;
            height: 80%;
            display: grid;
            gap: 0.25rem;
        }

        .layout-grid-1 {
            grid-template-columns: 1fr;
        }

        .layout-grid-2 {
            grid-template-columns: 1fr 1fr;
        }

        .layout-grid-3 {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .layout-grid-2-2 {
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
        }

        .layout-grid-3-3 {
            grid-template-columns: 1fr 1fr 1fr;
            grid-template-rows: 1fr 1fr 1fr;
        }

        .layout-grid-3-2 {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            width: 80%;
            height: 80%;
        }

        .layout-grid-3-2 .top-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0.25rem;
            height: 50%;
        }

        .layout-grid-3-2 .bottom-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.25rem;
            height: 50%;
            padding: 0 15%;
        }

        #formatted-content {
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 1rem;
            background: white;
        }

        .spinner {
            width: 1.25rem;
            height: 1.25rem;
            border: 3px solid #334155;
            border-top: 3px solid #ff6b35;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .image-preview img {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            max-width: 250px;
            max-height: 250px;
            object-fit: cover;
            margin-bottom: 0.75rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div style="background: #1e293b; border-bottom: 1px solid #334155; padding: 1.25rem 2rem;">
        <div style="max-width: 1800px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 600; color: #e2e8f0; margin: 0;">
                    <i class="fas fa-envelope-open-text" style="color: #ff6b35; margin-right: 0.5rem;"></i>
                    Email Template Generator
                </h1>
                <p style="margin: 0.25rem 0 0 0; color: #94a3b8; font-size: 0.875rem;">Create professional, responsive email templates</p>
            </div>
        </div>
    </div>

    <div x-data="{ 
        isLoading: false,
        showToast: false,
        toastMessage: '',
        toastType: 'success',
        selectedFile: null,
        currentStep: <?php echo $current_step; ?>,
        resetModal: {
            show: false
        }
    }" style="max-width: 1800px; margin: 0 auto; padding: 2rem;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Form Section -->
            <div style="overflow-y: auto; max-height: calc(100vh - 150px);">
                <div class="progress-stepper">
                    <div class="step" :class="{'active': currentStep >= 1, 'completed': currentStep > 1}">
                        <div class="step-circle">
                            <template x-if="currentStep > 1">
                                <i class="fas fa-check"></i>
                            </template>
                            <template x-if="currentStep <= 1">
                                <span>1</span>
                            </template>
                        </div>
                        <div class="step-title">Content</div>
                    </div>
                    <div class="step" :class="{'active': currentStep >= 2, 'completed': currentStep > 2}">
                        <div class="step-circle">
                            <template x-if="currentStep > 2">
                                <i class="fas fa-check"></i>
                            </template>
                            <template x-if="currentStep <= 2">
                                <span>2</span>
                            </template>
                        </div>
                        <div class="step-title">Images</div>
                    </div>
                    <div class="step" :class="{'active': currentStep >= 3, 'completed': currentStep > 3}">
                        <div class="step-circle">
                            <template x-if="currentStep > 3">
                                <i class="fas fa-check"></i>
                            </template>
                            <template x-if="currentStep <= 3">
                                <span>3</span>
                            </template>
                        </div>
                        <div class="step-title">Signature</div>
                    </div>
                    <div class="step" :class="{'active': currentStep >= 4}">
                        <div class="step-circle">
                            <template x-if="currentStep > 4">
                                <i class="fas fa-check"></i>
                            </template>
                            <template x-if="currentStep <= 4">
                                <span>4</span>
                            </template>
                        </div>
                        <div class="step-title">Generate</div>
                    </div>
                </div>
                
                <form method="post" enctype="multipart/form-data" @submit="isLoading = true">
                    <!-- Header Image -->
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <div class="card-header">
                            <i class="fas fa-image" style="color: #ff6b35; font-size: 1.25rem; margin-right: 1rem;"></i>
                            <h2 style="font-size: 1.125rem; font-weight: 600; margin: 0;">Header Image</h2>
                        </div>
                        <div class="card-body">
                            <div class="file-upload">
                                <label class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #ff6b35; margin-bottom: 0.5rem;"></i>
                                    <span style="color: #94a3b8;">Drop your header image or click to browse</span>
                                    <p style="margin: 0.5rem 0 0 0; font-size: 0.75rem; color: #64748b;">Recommended size: 750px width</p>
                                    <input type="file" name="header_image" accept="image/*"
                                        @change="selectedFile = $event.target.files[0]?.name">
                                </label>
                                <div x-show="selectedFile" class="selected-file" style="display: none;">
                                    <i class="fas fa-file-image" style="color: #ff6b35;"></i>
                                    <span x-text="selectedFile" style="margin-left: 0.5rem;"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Section -->
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <div class="card-header">
                            <i class="fas fa-pen-fancy" style="color: #ff6b35; font-size: 1.25rem; margin-right: 1rem;"></i>
                            <h2 style="font-size: 1.125rem; font-weight: 600; margin: 0;">Email Content</h2>
                        </div>
                        <div class="card-body">
                            <textarea id="rich-text-editor" name="body_content" style="height: 300px;"><?php echo isset($_POST['body_content']) ? $_POST['body_content'] : ''; ?></textarea>
                            <p style="margin: 0.75rem 0 0 0; font-size: 0.75rem; color: #64748b;">
                                <i class="fas fa-info-circle"></i> 
                                Important dates and keywords will be auto-highlighted
                            </p>
                        </div>
                    </div>

                    <!-- Layout Section -->
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <div class="card-header">
                            <i class="fas fa-th-large" style="color: #ff6b35; font-size: 1.25rem; margin-right: 1rem;"></i>
                            <h2 style="font-size: 1.125rem; font-weight: 600; margin: 0;">Image Layout</h2>
                        </div>
                        <div class="card-body">
                            <div class="layout-options">
                                <!-- No Images -->
                                <div class="layout-option">
                                    <input type="radio" id="layout-none" name="layout_type" value="0" checked>
                                    <label for="layout-none" class="layout-content">
                                        <div class="layout-visual">
                                            <div class="option-check">
                                                <i class="fas fa-check" style="font-size: 0.75rem;"></i>
                                            </div>
                                            <i class="fas fa-ban" style="color: #64748b; font-size: 1.5rem;"></i>
                                        </div>
                                        <div class="layout-title">No Images</div>
                                    </label>
                                </div>
                                
                                <!-- Group Photo -->
                                <div class="layout-option">
                                    <input type="radio" id="layout-group" name="layout_type" value="group">
                                    <label for="layout-group" class="layout-content">
                                        <div class="layout-visual">
                                            <div class="option-check">
                                                <i class="fas fa-check" style="font-size: 0.75rem;"></i>
                                            </div>
                                            <i class="fas fa-users" style="color: #ff6b35; font-size: 1.5rem;"></i>
                                        </div>
                                        <div class="layout-title">Group Photo</div>
                                    </label>
                                </div>
                                
                                <!-- Single Employee -->
                                <div class="layout-option">
                                    <input type="radio" id="layout-single" name="layout_type" value="1">
                                    <label for="layout-single" class="layout-content">
                                        <div class="layout-visual">
                                            <div class="option-check">
                                                <i class="fas fa-check" style="font-size: 0.75rem;"></i>
                                            </div>
                                            <div class="layout-grid layout-grid-1">
                                                <div class="grid-item"></div>
                                            </div>
                                        </div>
                                        <div class="layout-title">Single</div>
                                    </label>
                                </div>
                                
                                <!-- Two Employees -->
                                <div class="layout-option">
                                    <input type="radio" id="layout-two" name="layout_type" value="2">
                                    <label for="layout-two" class="layout-content">
                                        <div class="layout-visual">
                                            <div class="option-check">
                                                <i class="fas fa-check" style="font-size: 0.75rem;"></i>
                                            </div>
                                            <div class="layout-grid layout-grid-2">
                                                <div class="grid-item"></div>
                                                <div class="grid-item"></div>
                                            </div>
                                        </div>
                                        <div class="layout-title">Two (1×2)</div>
                                    </label>
                                </div>
                                
                                <!-- Three Employees -->
                                <div class="layout-option">
                                    <input type="radio" id="layout-three" name="layout_type" value="3">
                                    <label for="layout-three" class="layout-content">
                                        <div class="layout-visual">
                                            <div class="option-check">
                                                <i class="fas fa-check" style="font-size: 0.75rem;"></i>
                                            </div>
                                            <div class="layout-grid layout-grid-3">
                                                <div class="grid-item"></div>
                                                <div class="grid-item"></div>
                                                <div class="grid-item"></div>
                                            </div>
                                        </div>
                                        <div class="layout-title">Three (1×3)</div>
                                    </label>
                                </div>
                                
                                <!-- Four Employees -->
                                <div class="layout-option">
                                    <input type="radio" id="layout-four" name="layout_type" value="2-2">
                                    <label for="layout-four" class="layout-content">
                                        <div class="layout-visual">
                                            <div class="option-check">
                                                <i class="fas fa-check" style="font-size: 0.75rem;"></i>
                                            </div>
                                            <div class="layout-grid layout-grid-2-2">
                                                <div class="grid-item"></div>
                                                <div class="grid-item"></div>
                                                <div class="grid-item"></div>
                                                <div class="grid-item"></div>
                                            </div>
                                        </div>
                                        <div class="layout-title">Four (2×2)</div>
                                    </label>
                                </div>
                                
                                <!-- Five Employees -->
                                <div class="layout-option">
                                    <input type="radio" id="layout-five" name="layout_type" value="3-2">
                                    <label for="layout-five" class="layout-content">
                                        <div class="layout-visual">
                                            <div class="option-check">
                                                <i class="fas fa-check" style="font-size: 0.75rem;"></i>
                                            </div>
                                            <div class="layout-grid-3-2">
                                                <div class="top-row">
                                                    <div class="grid-item"></div>
                                                    <div class="grid-item"></div>
                                                    <div class="grid-item"></div>
                                                </div>
                                                <div class="bottom-row">
                                                    <div class="grid-item"></div>
                                                    <div class="grid-item"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="layout-title">Five (3-2)</div>
                                    </label>
                                </div>
                                
                                <!-- Nine Employees -->
                                <div class="layout-option">
                                    <input type="radio" id="layout-nine" name="layout_type" value="3-3">
                                    <label for="layout-nine" class="layout-content">
                                        <div class="layout-visual">
                                            <div class="option-check">
                                                <i class="fas fa-check" style="font-size: 0.75rem;"></i>
                                            </div>
                                            <div class="layout-grid layout-grid-3-3">
                                                <div class="grid-item"></div>
                                                <div class="grid-item"></div>
                                                <div class="grid-item"></div>
                                                <div class="grid-item"></div>
                                                <div class="grid-item"></div>
                                                <div class="grid-item"></div>
                                                <div class="grid-item"></div>
                                                <div class="grid-item"></div>
                                                <div class="grid-item"></div>
                                            </div>
                                        </div>
                                        <div class="layout-title">Nine (3×3)</div>
                                    </label>
                                </div>
                            </div>
                            
                            <div id="image-uploads" style="margin-top: 1.5rem;"></div>
                        </div>
                    </div>

                    <!-- Signature Section -->
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <div class="card-header">
                            <i class="fas fa-signature" style="color: #ff6b35; font-size: 1.25rem; margin-right: 1rem;"></i>
                            <h2 style="font-size: 1.125rem; font-weight: 600; margin: 0;">Email Signature</h2>
                        </div>
                        <div class="card-body">
                            <div style="margin-bottom: 1rem;">
                                <label class="form-label">Regards Text</label>
                                <input type="text" name="regards_text" placeholder="Best Regards, Sincerely, etc." 
                                    class="form-control" value="<?php echo isset($_POST['regards_text']) ? htmlspecialchars($_POST['regards_text']) : 'Best Regards,'; ?>">
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <label class="form-label">Signature Name</label>
                                    <input type="text" name="regards_name" placeholder="Your Name" 
                                        class="form-control" value="<?php echo isset($_POST['regards_name']) ? htmlspecialchars($_POST['regards_name']) : ''; ?>">
                                </div>
                                <div>
                                    <label class="form-label">Title/Position</label>
                                    <input type="text" name="regards_title" placeholder="Your Title/Position" 
                                        class="form-control" value="<?php echo isset($_POST['regards_title']) ? htmlspecialchars($_POST['regards_title']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; gap: 1rem;">
                        <button type="button" @click="resetModal.show = true" class="btn btn-secondary">
                            <i class="fas fa-redo"></i>Reset
                        </button>
                        <button type="submit" class="btn btn-primary" :disabled="isLoading">
                            <template x-if="isLoading">
                                <div class="spinner"></div>
                            </template>
                            <i class="fas fa-paper-plane"></i>
                            Generate Template
                        </button>
                    </div>
                </form>
            </div>

            <!-- Preview Section -->
            <div class="preview-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2 style="font-size: 1.25rem; font-weight: 600; margin: 0;">Preview</h2>
                    <?php if (isset($preview_html) && !empty($preview_html)): ?>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <button onclick="copyForOutlook()" class="btn btn-primary" style="font-size: 0.8125rem; padding: 0.5rem 1rem;">
                            <i class="fas fa-envelope"></i>Copy for Outlook
                        </button>
                        <button onclick="copyForGmail()" class="btn btn-success" style="font-size: 0.8125rem; padding: 0.5rem 1rem;">
                            <i class="fas fa-envelope"></i>Copy for Gmail
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (isset($preview_html) && !empty($preview_html)): ?>
                    <div id="formatted-content">
                        <?php echo $preview_html; ?>
                    </div>
                <?php else: ?>
                    <div class="preview-empty">
                        <i class="fas fa-envelope-open-text"></i>
                        <p style="font-size: 1.125rem; font-weight: 600; color: #94a3b8; margin: 0 0 0.5rem 0;">Your email preview will appear here</p>
                        <p style="color: #64748b; margin: 0;">Generate a template to see how your email will look</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reset Modal -->
        <div class="modal-overlay" :class="{'show': resetModal.show}" @click.self="resetModal.show = false">
            <div class="modal-container">
                <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(239, 68, 68, 0.1); display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                        <i class="fas fa-exclamation-triangle" style="color: #ef4444; font-size: 1.5rem;"></i>
                    </div>
                    <h3 style="font-size: 1.25rem; font-weight: 600; margin: 0;">Reset Form?</h3>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <p style="color: #94a3b8; margin: 0;">
                        Are you sure you want to reset the form? All your inputs and changes will be lost.
                    </p>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn btn-secondary" @click="resetModal.show = false">
                        <i class="fas fa-times"></i>Cancel
                    </button>
                    <button type="button" class="btn" style="background: #ef4444; color: white;" @click="performReset()">
                        <i class="fas fa-redo"></i>Reset Form
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast Notification -->
        <div x-show="showToast" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-x-full"
            x-transition:enter-end="opacity-100 transform translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-x-0"
            x-transition:leave-end="opacity-0 transform translate-x-full"
            class="toast"
            :class="{ 'success': toastType === 'success', 'error': toastType === 'error' }">
            <i class="fas" :class="{ 'fa-check-circle': toastType === 'success', 'fa-times-circle': toastType === 'error' }"></i>
            <span x-text="toastMessage"></span>
        </div>
    </div>

    <script>
        function updateImageUploadFields(layoutType) {
            const container = document.getElementById('image-uploads');
            container.innerHTML = '';

            if (layoutType === 'group') {
                const div = document.createElement('div');
                div.className = 'card';
                div.style.marginTop = '1rem';
                div.innerHTML = `
                    <div class="card-header">
                        <i class="fas fa-users" style="color: #ff6b35; font-size: 1.125rem; margin-right: 0.75rem;"></i>
                        <h3 style="font-size: 1rem; font-weight: 600; margin: 0;">Group Photo</h3>
                    </div>
                    <div class="card-body">
                        <div class="file-upload">
                            <label class="file-upload-label">
                                <i class="fas fa-users" style="font-size: 2rem; color: #ff6b35; margin-bottom: 0.5rem;"></i>
                                <span style="color: #94a3b8;">Drop your group photo or click to browse</span>
                                <input type="file" name="group_image" accept="image/*" onchange="updateFileName(this)">
                            </label>
                            <div class="selected-file" style="display: none;"></div>
                        </div>
                        <div style="margin-top: 1rem;">
                            <label class="form-label">Caption (optional)</label>
                            <input type="text" name="group_caption" placeholder="Group photo caption" class="form-control">
                        </div>
                    </div>
                `;
                container.appendChild(div);
                return;
            }

            let numImages = 0;
            switch(layoutType) {
                case '1': numImages = 1; break;
                case '2': numImages = 2; break;
                case '3': numImages = 3; break;
                case '2-2': numImages = 4; break;
                case '3-2': numImages = 5; break;
                case '3-3': numImages = 9; break;
            }

            for(let i = 1; i <= numImages; i++) {
                const div = document.createElement('div');
                div.className = 'card';
                div.style.marginTop = '1rem';
                div.innerHTML = `
                    <div class="card-header">
                        <i class="fas fa-user-circle" style="color: #ff6b35; font-size: 1.125rem; margin-right: 0.75rem;"></i>
                        <h3 style="font-size: 1rem; font-weight: 600; margin: 0;">Employee ${i}</h3>
                    </div>
                    <div class="card-body">
                        <div class="file-upload">
                            <label class="file-upload-label">
                                <i class="fas fa-user-circle" style="font-size: 2rem; color: #ff6b35; margin-bottom: 0.5rem;"></i>
                                <span style="color: #94a3b8;">Drop employee image or click to browse</span>
                                <input type="file" name="employee_image_${i}" accept="image/*" onchange="updateFileName(this)">
                            </label>
                            <div class="selected-file" style="display: none;"></div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                            <div>
                                <label class="form-label">Name</label>
                                <input type="text" name="employee_name_${i}" placeholder="Employee Name" class="form-control">
                            </div>
                            <div>
                                <label class="form-label">Title</label>
                                <input type="text" name="employee_title_${i}" placeholder="Employee Title" class="form-control">
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(div);
            }
        }

        function updateFileName(input) {
            const fileNameDisplay = input.parentElement.nextElementSibling;
            if (input.files.length > 0) {
                fileNameDisplay.innerHTML = `<i class="fas fa-file-image" style="color: #ff6b35;"></i><span style="margin-left: 0.5rem;">${input.files[0].name}</span>`;
                fileNameDisplay.style.display = 'flex';
            } else {
                fileNameDisplay.style.display = 'none';
            }
        }

        function showToast(message, type = 'success', duration = 3000) {
            const appData = document.querySelector('[x-data]').__x.$data;
            appData.toastMessage = message;
            appData.toastType = type;
            appData.showToast = true;
            
            setTimeout(() => {
                appData.showToast = false;
            }, duration);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize TinyMCE
            tinymce.init({
                selector: '#rich-text-editor',
                height: 300,
                menubar: false,
                plugins: 'code lists link table',
                toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link table | code',
                content_style: 'body { font-family: Arial, sans-serif; }',
                skin: 'oxide-dark',
                content_css: 'dark'
            });

            // Add event listeners to layout type radio buttons
            document.querySelectorAll('input[name="layout_type"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    updateImageUploadFields(this.value);
                });
            });

            // Alpine.js method for form reset
            if (document.querySelector('[x-data]').__x) {
                document.querySelector('[x-data]').__x.$data.performReset = function() {
                    this.resetModal.show = false;
                    
                    if (typeof tinymce !== 'undefined' && tinymce.get('rich-text-editor')) {
                        tinymce.get('rich-text-editor').setContent('');
                    }
                    
                    document.querySelectorAll('input[type="file"]').forEach(fileInput => {
                        fileInput.value = '';
                    });
                    
                    document.querySelectorAll('.selected-file').forEach(element => {
                        element.style.display = 'none';
                    });
                    
                    const defaultLayout = document.getElementById('layout-none');
                    if (defaultLayout) {
                        defaultLayout.checked = true;
                        updateImageUploadFields('0');
                    }
                    
                    document.querySelectorAll('input[type="text"]').forEach(input => {
                        if (input.name !== 'regards_text') {
                            input.value = '';
                        }
                    });
                    
                    this.currentStep = 1;
                    this.selectedFile = null;
                    
                    showToast('Form has been reset successfully', 'success');
                    
                    setTimeout(function() {
                        window.location.href = window.location.pathname;
                    }, 1000);
                };
            }
        });

        async function copyWithFormattingPreserved(element) {
            if (navigator.clipboard && typeof navigator.clipboard.write === 'function') {
                try {
                    const htmlBlob = new Blob([element.outerHTML], { type: 'text/html' });
                    const textBlob = new Blob([element.innerText], { type: 'text/plain' });
                    
                    const clipboardItem = new ClipboardItem({
                        'text/html': htmlBlob,
                        'text/plain': textBlob
                    });
                    
                    await navigator.clipboard.write([clipboardItem]);
                    return { success: true, message: 'Content copied with formatting!' };
                } catch (err) {
                    console.warn('Clipboard API failed', err);
                }
            }
            
            try {
                const range = document.createRange();
                range.selectNodeContents(element);
                
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
                
                const success = document.execCommand('copy');
                selection.removeAllRanges();
                
                if (success) {
                    return { success: true, message: 'Content copied!' };
                }
            } catch (err) {
                console.warn('Copy failed', err);
            }
            
            return { success: false, message: 'Unable to copy content' };
        }

        function copyForOutlook() {
            const content = document.getElementById('formatted-content');
            if (!content) {
                showToast('Nothing to copy', 'error');
                return;
            }
            
            copyWithFormattingPreserved(content)
                .then(result => {
                    if (result.success) {
                        showToast('Content copied for Outlook!', 'success');
                    } else {
                        showToast('Please select and copy manually', 'error');
                    }
                });
        }

        function copyForGmail() {
            const content = document.getElementById('formatted-content');
            if (!content) {
                showToast('Nothing to copy', 'error');
                return;
            }
            
            copyWithFormattingPreserved(content)
                .then(result => {
                    if (result.success) {
                        showToast('Content copied for Gmail!', 'success');
                    } else {
                        showToast('Please select and copy manually', 'error');
                    }
                });
        }
    </script>
</body>
</html>

<?php
// Template generation functions (keeping the same as original)
function generateEmailTemplate($options) {
    $defaults = [
        'header_image' => '',
        'body_content' => '',
        'signature_name' => '',
        'signature_title' => '',
        'employee_images' => [],
        'layout_type' => '0',
        'footer_image' => '',
        'employee_details' => [],
        'group_image' => '',
        'group_caption' => '',
        'regards_text' => 'Best Regards,'
    ];
    
    $options = array_merge($defaults, $options);
    extract($options);
    
    $fontFamily = '"Proxima Nova RG", "Proxima Nova", Arial, sans-serif';
    $emailWidth = 750;
    $body_content = processContentForOutlook($body_content);
    
    $template = '<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<style type="text/css">
    body { margin: 0; padding: 0; font-family: ' . $fontFamily . '; }
    table, td { border-collapse: collapse; font-family: ' . $fontFamily . '; }
    img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
    p { margin-top: 0; margin-bottom: 12pt; line-height: 1.5; }
</style>
</head>
<body style="margin:0;padding:0;background-color:#ffffff;font-family:' . $fontFamily . ';">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" width="' . $emailWidth . '" style="width:' . $emailWidth . 'px;max-width:100%;">
    <tr>
        <td align="center" style="padding:0;" width="' . $emailWidth . '">
            ' . ($header_image ? '<img src="' . $header_image . '" alt="Header" width="' . $emailWidth . '" style="width:' . $emailWidth . 'px;max-width:100%;display:block;" />' : '') . '
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:30px 50px;" width="' . $emailWidth . '">
            <div style="font-family:' . $fontFamily . ';line-height:1.5;">
                ' . $body_content . '
            </div>
        </td>
    </tr>';

    if ($layout_type === 'group' && !empty($group_image)) {
        $template .= generateGroupImage($group_image, $group_caption, $fontFamily, $emailWidth);
    } else if (!empty($employee_images)) {
        $template .= generateImageGrid($employee_images, $layout_type, $employee_details, $fontFamily, $emailWidth);
    }

    $template .= '
    <tr>
        <td align="left" style="padding:10px 50px;" width="' . $emailWidth . '">
            <p style="margin:0 0 12pt 0;line-height:1.5;color:#333333;font-family:' . $fontFamily . ';">
                <strong>' . htmlspecialchars($regards_text) . '</strong>
            </p>
            <p style="margin:0 0 12pt 0;line-height:1.5;color:#333333;font-family:' . $fontFamily . ';">
                <strong>' . htmlspecialchars($signature_name) . '</strong><br>
                <span style="color:#666666;">' . htmlspecialchars($signature_title) . '</span>
            </p>
        </td>
    </tr>
    <tr>
        <td align="center" style="background-color:#000000;padding:15px;" width="' . $emailWidth . '">
            <div style="font-family:' . $fontFamily . ';line-height:1.5;color:#FFFFFF;text-align:center;">
                Padak (Pvt) Ltd, Batticaloa, Sri Lanka,<br>
                Contact Number: +94 710815522
            </div>
        </td>
    </tr>
</table>
</body>
</html>';

    return $template;
}

function generateGroupImage($group_image, $group_caption, $fontFamily, $emailWidth) {
    if (empty($group_image)) return '';
    
    $imageWidth = 600;
    $html = '<tr><td align="center" style="padding:20px 0;" width="' . $emailWidth . '">';
    $html .= '<div style="text-align:center;max-width:' . $imageWidth . 'px;margin:0 auto;">';
    $html .= '<img src="' . $group_image . '" width="' . $imageWidth . '" style="width:' . $imageWidth . 'px;max-width:100%;display:block;border-radius:5px;" />';
    
    if (!empty($group_caption)) {
        $html .= '<div style="padding-top:15px;font-family:' . $fontFamily . ';">';
        $html .= '<span style="font-style:italic;font-size:14px;">' . htmlspecialchars($group_caption) . '</span>';
        $html .= '</div>';
    }
    
    $html .= '</div></td></tr>';
    return $html;
}

function generateImageGrid($images, $layout_type, $employee_details, $fontFamily, $emailWidth) {
    $filteredImages = [];
    $validPositions = [];
    foreach ($images as $index => $imageData) {
        if (!empty($imageData)) {
            $filteredImages[] = $imageData;
            $validPositions[] = $index;
        }
    }
    
    if (empty($filteredImages)) return '';
    
    $html = '<tr><td align="center" width="' . $emailWidth . '"><table cellspacing="0" cellpadding="0" border="0" align="center" width="100%">';
    
    $generateEmployeeCell = function($index, $imageData, $details, $cellWidth = 200) use ($fontFamily) {
        $html = '<td align="center" style="padding:10px;width:' . $cellWidth . 'px;">';
        $html .= '<div style="text-align:center;">';
        
        if (!empty($imageData)) {
            $html .= '<img src="' . $imageData . '" width="200" height="200" style="display:block;border-radius:5px;" />';
        }
        
        if (isset($details[$index]) && !empty($details[$index]['name'])) {
            $html .= '<div style="padding-top:15px;">';
            $html .= '<span style="font-weight:bold;font-size:16px;">' . htmlspecialchars($details[$index]['name']) . '</span><br>';
            $html .= '<span style="font-size:14px;">' . htmlspecialchars($details[$index]['title']) . '</span>';
            $html .= '</div>';
        }
        $html .= '</div></td>';
        
        return $html;
    };
    
    $validImageCount = count($filteredImages);
    
    if ($validImageCount === 1) {
        $html .= '<tr><td align="center"><table cellpadding="0" cellspacing="0" border="0"><tr>';
        $html .= $generateEmployeeCell($validPositions[0], $filteredImages[0], $employee_details, 200);
        $html .= '</tr></table></td></tr>';
    } else if ($validImageCount === 2) {
        $html .= '<tr><td align="center"><table cellpadding="0" cellspacing="0" border="0"><tr>';
        $html .= $generateEmployeeCell($validPositions[0], $filteredImages[0], $employee_details, 200);
        $html .= $generateEmployeeCell($validPositions[1], $filteredImages[1], $employee_details, 200);
        $html .= '</tr></table></td></tr>';
    } else {
        for ($row = 0; $row < ceil($validImageCount / 3); $row++) {
            $html .= '<tr><td align="center"><table cellpadding="0" cellspacing="0" border="0"><tr>';
            for ($col = 0; $col < 3; $col++) {
                $index = $row * 3 + $col;
                if ($index < $validImageCount) {
                    $html .= $generateEmployeeCell($validPositions[$index], $filteredImages[$index], $employee_details, 200);
                }
            }
            $html .= '</tr></table></td></tr>';
        }
    }
    
    $html .= '</table></td></tr>';
    return $html;
}

function processContentForOutlook($html) {
    if (empty($html)) return '';
    
    $fontFamily = '"Proxima Nova RG", "Proxima Nova", Arial, sans-serif';
    
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = true;
    
    libxml_use_internal_errors(true);
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    
    $xpath = new DOMXPath($dom);
    $allElements = $xpath->query('//*');
    
    if ($allElements) {
        foreach ($allElements as $element) {
            $style = $element->getAttribute('style');
            $styles = array();
            
            if (!empty($style)) {
                $styleAttrs = explode(';', $style);
                foreach ($styleAttrs as $attr) {
                    $attr = trim($attr);
                    if (empty($attr)) continue;
                    
                    $parts = explode(':', $attr, 2);
                    if (count($parts) == 2) {
                        $property = trim(strtolower($parts[0]));
                        $value = trim($parts[1]);
                        $styles[$property] = $value;
                    }
                }
            }
            
            $styles['font-family'] = $fontFamily . ' !important';
            
            $newStyle = '';
            foreach ($styles as $property => $value) {
                $newStyle .= $property . ': ' . $value . '; ';
            }
            
            $element->setAttribute('style', trim($newStyle));
        }
    }
    
    $html = $dom->saveHTML();
    $html = preg_replace('/<\?xml encoding="utf-8" \?>/i', '', $html);
    $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?head[^>]*>/i', '', $html);
    $html = preg_replace('/<\/?body[^>]*>/i', '', $html);
    
    return $html;
}
?>