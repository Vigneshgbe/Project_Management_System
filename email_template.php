<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';

requireLogin();
renderLayout('Email Template Generator', 'email_template');

// Enable error logging for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure upload directory exists
$upload_dir = "uploads/email_templates/";
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

// Template generation functions
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

<style>
/* Email Template Generator Specific Styles */
.etg-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.etg-form-section {
    overflow-y: auto;
    max-height: calc(100vh - 180px);
}

.etg-preview-section {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 20px;
    max-height: calc(100vh - 180px);
    overflow-y: auto;
    position: sticky;
    top: 78px;
}

/* Progress Stepper */
.progress-stepper {
    display: flex;
    justify-content: space-between;
    margin-bottom: 24px;
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
    top: 18px;
    left: 50%;
    width: 100%;
    height: 2px;
    background-color: var(--border);
    z-index: 0;
}

.step.active:not(:last-child)::after,
.step.completed:not(:last-child)::after {
    background-color: var(--orange);
}

.step-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: var(--bg3);
    color: var(--text3);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    position: relative;
    z-index: 1;
    font-weight: 600;
    font-size: 14px;
    border: 2px solid var(--border);
}

.step.active .step-circle {
    background: var(--orange);
    color: white;
    border-color: var(--orange);
    box-shadow: 0 0 0 4px var(--orange-bg);
}

.step.completed .step-circle {
    background: var(--green);
    color: white;
    border-color: var(--green);
}

.step-title {
    font-size: 12px;
    color: var(--text3);
    font-weight: 500;
}

.step.active .step-title {
    color: var(--orange);
    font-weight: 600;
}

.step.completed .step-title {
    color: var(--green);
}

/* File Upload */
.file-upload-label {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px;
    background: var(--bg3);
    border: 2px dashed var(--border);
    border-radius: var(--radius);
    cursor: pointer;
    transition: all 0.3s ease;
    flex-direction: column;
    gap: 8px;
}

.file-upload-label:hover {
    background: var(--bg4);
    border-color: var(--orange);
}

.file-upload input[type="file"] {
    position: absolute;
    width: 0;
    height: 0;
    opacity: 0;
}

.selected-file {
    margin-top: 12px;
    padding: 10px 12px;
    background: var(--bg4);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    color: var(--text2);
    font-size: 13px;
    gap: 8px;
}

/* Layout Options */
.layout-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
}

.layout-option {
    position: relative;
    transition: all 0.3s ease;
    border: 2px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    cursor: pointer;
}

.layout-option:hover {
    border-color: var(--orange);
    transform: translateY(-2px);
}

.layout-option input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.layout-option input[type="radio"]:checked + .layout-content {
    border-color: var(--orange);
    background-color: var(--orange-bg);
}

.layout-option input[type="radio"]:checked + .layout-content .option-check {
    opacity: 1;
    transform: scale(1);
}

.layout-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 14px;
    cursor: pointer;
    border: 2px solid transparent;
    border-radius: var(--radius);
    background: var(--bg3);
    height: 100%;
}

.layout-visual {
    background-color: var(--bg4);
    width: 100%;
    height: 80px;
    border-radius: var(--radius-sm);
    margin-bottom: 10px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.option-check {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 20px;
    height: 20px;
    background: var(--orange);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: scale(0.5);
    transition: all 0.2s ease;
    font-size: 11px;
}

.layout-title {
    font-weight: 500;
    color: var(--text);
    text-align: center;
    font-size: 12px;
}

/* Grid items for layout preview */
.grid-item {
    background-color: var(--text3);
    border-radius: 3px;
}

.layout-grid {
    width: 80%;
    height: 80%;
    display: grid;
    gap: 3px;
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
    gap: 3px;
    width: 80%;
    height: 80%;
}

.layout-grid-3-2 .top-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 3px;
    height: 50%;
}

.layout-grid-3-2 .bottom-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3px;
    height: 50%;
    padding: 0 15%;
}

/* Preview */
#formatted-content {
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 16px;
    background: white;
}

.preview-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    background: var(--bg3);
    border-radius: var(--radius);
    text-align: center;
    border: 2px dashed var(--border);
}

.preview-empty .icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.3;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}

.preview-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

/* Spinner */
.spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,.3);
    border-top: 2px solid white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* TinyMCE Dark Mode Support */
[data-theme="dark"] .tox .tox-edit-area__iframe {
    background: var(--bg3) !important;
}

[data-theme="dark"] .tox-tinymce {
    border-color: var(--border) !important;
}

/* Responsive */
@media(max-width:1200px) {
    .etg-wrapper {
        grid-template-columns: 1fr;
    }
    
    .etg-preview-section {
        position: static;
        max-height: 600px;
    }
}

@media(max-width:768px) {
    .layout-options {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .preview-buttons {
        width: 100%;
    }
    
    .preview-buttons .btn {
        flex: 1;
    }
}
</style>

<div class="etg-wrapper">
    <!-- Form Section -->
    <div class="etg-form-section">
        <div class="progress-stepper">
            <div class="step <?= $current_step >= 1 ? 'active' : '' ?> <?= $current_step > 1 ? 'completed' : '' ?>">
                <div class="step-circle">
                    <?php if ($current_step > 1): ?>
                        ✓
                    <?php else: ?>
                        1
                    <?php endif; ?>
                </div>
                <div class="step-title">Content</div>
            </div>
            <div class="step <?= $current_step >= 2 ? 'active' : '' ?> <?= $current_step > 2 ? 'completed' : '' ?>">
                <div class="step-circle">
                    <?php if ($current_step > 2): ?>
                        ✓
                    <?php else: ?>
                        2
                    <?php endif; ?>
                </div>
                <div class="step-title">Images</div>
            </div>
            <div class="step <?= $current_step >= 3 ? 'active' : '' ?> <?= $current_step > 3 ? 'completed' : '' ?>">
                <div class="step-circle">
                    <?php if ($current_step > 3): ?>
                        ✓
                    <?php else: ?>
                        3
                    <?php endif; ?>
                </div>
                <div class="step-title">Signature</div>
            </div>
            <div class="step <?= $current_step >= 4 ? 'active' : '' ?>">
                <div class="step-circle">
                    <?php if ($current_step > 4): ?>
                        ✓
                    <?php else: ?>
                        4
                    <?php endif; ?>
                </div>
                <div class="step-title">Generate</div>
            </div>
        </div>
        
        <form method="post" enctype="multipart/form-data" id="emailTemplateForm">
            <!-- Header Image -->
            <div class="card" style="margin-bottom: 16px;">
                <div class="card-header">
                    <div class="card-title">📷 Header Image</div>
                </div>
                <div style="padding: 20px;">
                    <div class="file-upload">
                        <label class="file-upload-label">
                            <span style="font-size: 32px; opacity: 0.5;">☁️</span>
                            <span style="color: var(--text2); font-size: 13px;">Drop your header image or click to browse</span>
                            <span style="font-size: 11px; color: var(--text3); margin-top: 4px;">Recommended size: 750px width</span>
                            <input type="file" name="header_image" accept="image/*" onchange="updateFileName(this, 'header')">
                        </label>
                        <div class="selected-file" id="selected-file-header" style="display: none;">
                            <span>📁</span>
                            <span class="filename"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Section -->
            <div class="card" style="margin-bottom: 16px;">
                <div class="card-header">
                    <div class="card-title">✏️ Email Content</div>
                </div>
                <div style="padding: 20px;">
                    <textarea id="rich-text-editor" name="body_content" style="height: 300px;"><?= isset($_POST['body_content']) ? htmlspecialchars($_POST['body_content']) : '' ?></textarea>
                    <p style="margin: 12px 0 0 0; font-size: 11px; color: var(--text3);">
                        💡 Use the editor to format your content with bold, italic, lists, and more
                    </p>
                </div>
            </div>

            <!-- Layout Section -->
            <div class="card" style="margin-bottom: 16px;">
                <div class="card-header">
                    <div class="card-title">🎨 Image Layout</div>
                </div>
                <div style="padding: 20px;">
                    <div class="layout-options">
                        <!-- No Images -->
                        <div class="layout-option">
                            <input type="radio" id="layout-none" name="layout_type" value="0" checked onchange="updateImageUploadFields(this.value)">
                            <label for="layout-none" class="layout-content">
                                <div class="layout-visual">
                                    <div class="option-check">✓</div>
                                    <span style="font-size: 24px; opacity: 0.3;">🚫</span>
                                </div>
                                <div class="layout-title">No Images</div>
                            </label>
                        </div>
                        
                        <!-- Group Photo -->
                        <div class="layout-option">
                            <input type="radio" id="layout-group" name="layout_type" value="group" onchange="updateImageUploadFields(this.value)">
                            <label for="layout-group" class="layout-content">
                                <div class="layout-visual">
                                    <div class="option-check">✓</div>
                                    <span style="font-size: 24px; opacity: 0.5;">👥</span>
                                </div>
                                <div class="layout-title">Group Photo</div>
                            </label>
                        </div>
                        
                        <!-- Single Employee -->
                        <div class="layout-option">
                            <input type="radio" id="layout-single" name="layout_type" value="1" onchange="updateImageUploadFields(this.value)">
                            <label for="layout-single" class="layout-content">
                                <div class="layout-visual">
                                    <div class="option-check">✓</div>
                                    <div class="layout-grid layout-grid-1">
                                        <div class="grid-item"></div>
                                    </div>
                                </div>
                                <div class="layout-title">Single</div>
                            </label>
                        </div>
                        
                        <!-- Two Employees -->
                        <div class="layout-option">
                            <input type="radio" id="layout-two" name="layout_type" value="2" onchange="updateImageUploadFields(this.value)">
                            <label for="layout-two" class="layout-content">
                                <div class="layout-visual">
                                    <div class="option-check">✓</div>
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
                            <input type="radio" id="layout-three" name="layout_type" value="3" onchange="updateImageUploadFields(this.value)">
                            <label for="layout-three" class="layout-content">
                                <div class="layout-visual">
                                    <div class="option-check">✓</div>
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
                            <input type="radio" id="layout-four" name="layout_type" value="2-2" onchange="updateImageUploadFields(this.value)">
                            <label for="layout-four" class="layout-content">
                                <div class="layout-visual">
                                    <div class="option-check">✓</div>
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
                            <input type="radio" id="layout-five" name="layout_type" value="3-2" onchange="updateImageUploadFields(this.value)">
                            <label for="layout-five" class="layout-content">
                                <div class="layout-visual">
                                    <div class="option-check">✓</div>
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
                            <input type="radio" id="layout-nine" name="layout_type" value="3-3" onchange="updateImageUploadFields(this.value)">
                            <label for="layout-nine" class="layout-content">
                                <div class="layout-visual">
                                    <div class="option-check">✓</div>
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
                    
                    <div id="image-uploads" style="margin-top: 16px;"></div>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="card" style="margin-bottom: 16px;">
                <div class="card-header">
                    <div class="card-title">✍️ Email Signature</div>
                </div>
                <div style="padding: 20px;">
                    <div class="form-group">
                        <label class="form-label">Regards Text</label>
                        <input type="text" name="regards_text" placeholder="Best Regards, Sincerely, etc." 
                            class="form-control" value="<?= isset($_POST['regards_text']) ? htmlspecialchars($_POST['regards_text']) : 'Best Regards,' ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Signature Name</label>
                            <input type="text" name="regards_name" placeholder="Your Name" 
                                class="form-control" value="<?= isset($_POST['regards_name']) ? htmlspecialchars($_POST['regards_name']) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Title/Position</label>
                            <input type="text" name="regards_title" placeholder="Your Title/Position" 
                                class="form-control" value="<?= isset($_POST['regards_title']) ? htmlspecialchars($_POST['regards_title']) : '' ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; gap: 12px;">
                <button type="button" onclick="resetForm()" class="btn btn-ghost">
                    <span>🔄</span>Reset
                </button>
                <button type="submit" class="btn btn-primary" id="generateBtn">
                    <span>🚀</span>Generate Template
                </button>
            </div>
        </form>
    </div>

    <!-- Preview Section -->
    <div class="etg-preview-section">
        <div class="preview-header">
            <h2 style="font-size: 18px; font-weight: 600; margin: 0;">Preview</h2>
            <?php if (isset($preview_html) && !empty($preview_html)): ?>
            <div class="preview-buttons">
                <button onclick="copyForOutlook()" class="btn btn-sm btn-primary">
                    <span>📧</span>Copy for Outlook
                </button>
                <button onclick="copyForGmail()" class="btn btn-sm" style="background: var(--green); color: white;">
                    <span>📧</span>Copy for Gmail
                </button>
            </div>
            <?php endif; ?>
        </div>

        <?php if (isset($preview_html) && !empty($preview_html)): ?>
            <div id="formatted-content">
                <?= $preview_html ?>
            </div>
        <?php else: ?>
            <div class="preview-empty">
                <div class="icon">📧</div>
                <p style="font-size: 16px; font-weight: 600; color: var(--text2); margin: 0 0 8px 0;">Your email preview will appear here</p>
                <p style="color: var(--text3); margin: 0; font-size: 13px;">Generate a template to see how your email will look</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.7.0/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize TinyMCE with theme awareness
    function initTinyMCE() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const skin = currentTheme === 'dark' ? 'oxide-dark' : 'oxide';
        const contentCss = currentTheme === 'dark' ? 'dark' : 'default';
        
        if (tinymce.get('rich-text-editor')) {
            tinymce.get('rich-text-editor').remove();
        }
        
        tinymce.init({
            selector: '#rich-text-editor',
            height: 300,
            menubar: false,
            plugins: 'code lists link table',
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link table | code',
            content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
            skin: skin,
            content_css: contentCss
        });
    }
    
    initTinyMCE();
    
    // Reinitialize TinyMCE when theme changes
    document.addEventListener('themeChanged', function(e) {
        setTimeout(initTinyMCE, 100);
    });
    
    // Form submission handling
    const form = document.getElementById('emailTemplateForm');
    const generateBtn = document.getElementById('generateBtn');
    
    form.addEventListener('submit', function() {
        generateBtn.disabled = true;
        generateBtn.innerHTML = '<div class="spinner"></div><span>Generating...</span>';
    });
});

function updateImageUploadFields(layoutType) {
    const container = document.getElementById('image-uploads');
    container.innerHTML = '';

    if (layoutType === 'group') {
        const div = document.createElement('div');
        div.className = 'card';
        div.innerHTML = `
            <div class="card-header">
                <div class="card-title">👥 Group Photo</div>
            </div>
            <div style="padding: 20px;">
                <div class="file-upload">
                    <label class="file-upload-label">
                        <span style="font-size: 32px; opacity: 0.5;">👥</span>
                        <span style="color: var(--text2); font-size: 13px;">Drop your group photo or click to browse</span>
                        <input type="file" name="group_image" accept="image/*" onchange="updateFileName(this, 'group')">
                    </label>
                    <div class="selected-file" id="selected-file-group" style="display: none;">
                        <span>📁</span>
                        <span class="filename"></span>
                    </div>
                </div>
                <div class="form-group" style="margin-top: 16px; margin-bottom: 0;">
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
        div.style.marginTop = '12px';
        div.innerHTML = `
            <div class="card-header">
                <div class="card-title">👤 Employee ${i}</div>
            </div>
            <div style="padding: 20px;">
                <div class="file-upload">
                    <label class="file-upload-label">
                        <span style="font-size: 32px; opacity: 0.5;">👤</span>
                        <span style="color: var(--text2); font-size: 13px;">Drop employee image or click to browse</span>
                        <input type="file" name="employee_image_${i}" accept="image/*" onchange="updateFileName(this, 'employee-${i}')">
                    </label>
                    <div class="selected-file" id="selected-file-employee-${i}" style="display: none;">
                        <span>📁</span>
                        <span class="filename"></span>
                    </div>
                </div>
                <div class="form-row" style="margin-top: 16px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Name</label>
                        <input type="text" name="employee_name_${i}" placeholder="Employee Name" class="form-control">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Title</label>
                        <input type="text" name="employee_title_${i}" placeholder="Employee Title" class="form-control">
                    </div>
                </div>
            </div>
        `;
        container.appendChild(div);
    }
}

function updateFileName(input, type) {
    const fileNameDisplay = document.getElementById('selected-file-' + type);
    if (input.files.length > 0) {
        const filenameSpan = fileNameDisplay.querySelector('.filename');
        filenameSpan.textContent = input.files[0].name;
        fileNameDisplay.style.display = 'flex';
    } else {
        fileNameDisplay.style.display = 'none';
    }
}

function resetForm() {
    if (!confirm('Are you sure you want to reset the form? All your inputs and changes will be lost.')) {
        return;
    }
    
    // Reset TinyMCE
    if (typeof tinymce !== 'undefined' && tinymce.get('rich-text-editor')) {
        tinymce.get('rich-text-editor').setContent('');
    }
    
    // Reset all file inputs
    document.querySelectorAll('input[type="file"]').forEach(fileInput => {
        fileInput.value = '';
    });
    
    // Hide all selected file displays
    document.querySelectorAll('.selected-file').forEach(element => {
        element.style.display = 'none';
    });
    
    // Reset to default layout
    const defaultLayout = document.getElementById('layout-none');
    if (defaultLayout) {
        defaultLayout.checked = true;
        updateImageUploadFields('0');
    }
    
    // Reset text inputs except regards_text
    document.querySelectorAll('input[type="text"]').forEach(input => {
        if (input.name !== 'regards_text') {
            input.value = '';
        } else {
            input.value = 'Best Regards,';
        }
    });
    
    // Reset form via page reload to ensure clean state
    toast('Form has been reset successfully', 'success');
    setTimeout(function() {
        window.location.href = window.location.pathname;
    }, 800);
}

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
        toast('Nothing to copy', 'error');
        return;
    }
    
    copyWithFormattingPreserved(content)
        .then(result => {
            if (result.success) {
                toast('Content copied for Outlook!', 'success');
            } else {
                toast('Please select and copy manually', 'error');
            }
        });
}

function copyForGmail() {
    const content = document.getElementById('formatted-content');
    if (!content) {
        toast('Nothing to copy', 'error');
        return;
    }
    
    copyWithFormattingPreserved(content)
        .then(result => {
            if (result.success) {
                toast('Content copied for Gmail!', 'success');
            } else {
                toast('Please select and copy manually', 'error');
            }
        });
}
</script>

<?php
renderLayoutEnd();
?>