<?php
// email_template.php — Email Template Generator (Padak CRM)
// Requires: includes/layout.php (renderLayout / renderLayoutEnd), auth helpers

require_once 'includes/init.php'; // adjust path as needed
requireLogin();

// ─── Upload dir setup ────────────────────────────────────────────────────────
$upload_dir = "uploads/email_temp/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
function getBase64Image(string $path): string {
    if (!file_exists($path)) return '';
    $data = file_get_contents($path);
    if ($data === false) return '';
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $path);
    finfo_close($finfo);
    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

function saveUpload(string $key, string $dir): string {
    if (!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) return '';
    $tmp  = $_FILES[$key]['tmp_name'];
    if (!getimagesize($tmp)) return '';
    $ext  = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);
    $dest = $dir . uniqid('img_', true) . '.' . $ext;
    return move_uploaded_file($tmp, $dest) ? getBase64Image($dest) : '';
}

// ─── Process POST ─────────────────────────────────────────────────────────────
$preview_html = '';
$form_error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body_content    = $_POST['body_content']    ?? '';
    $regards_text    = $_POST['regards_text']    ?? 'Best Regards,';
    $signature_name  = $_POST['regards_name']    ?? '';
    $signature_title = $_POST['regards_title']   ?? '';
    $layout_type     = $_POST['layout_type']     ?? '0';

    $header_image_data = saveUpload('header_image', $upload_dir);

    if (empty($body_content)) {
        $form_error = 'Please provide email content before generating.';
    } else {
        $options = [
            'header_image'    => $header_image_data,
            'body_content'    => $body_content,
            'signature_name'  => $signature_name,
            'signature_title' => $signature_title,
            'layout_type'     => $layout_type,
            'regards_text'    => $regards_text,
        ];

        if ($layout_type === 'group') {
            $options['group_image']   = saveUpload('group_image', $upload_dir);
            $options['group_caption'] = $_POST['group_caption'] ?? '';
        } else {
            $maxMap = ['1'=>1,'2'=>2,'3'=>3,'2-2'=>4,'3-2'=>5,'3-3'=>9];
            $max    = $maxMap[$layout_type] ?? 0;
            $imgs   = [];
            $dets   = [];
            for ($i = 1; $i <= $max; $i++) {
                $imgs[] = saveUpload("employee_image_{$i}", $upload_dir);
                $dets[] = [
                    'name'  => $_POST["employee_name_{$i}"]  ?? '',
                    'title' => $_POST["employee_title_{$i}"] ?? '',
                ];
            }
            $options['employee_images']  = $imgs;
            $options['employee_details'] = $dets;
        }

        $preview_html = generateEmailTemplate($options);
    }
}

// ─── Template engine ─────────────────────────────────────────────────────────
function generateEmailTemplate(array $opts): string {
    $defaults = [
        'header_image'    => '',
        'body_content'    => '',
        'signature_name'  => '',
        'signature_title' => '',
        'employee_images' => [],
        'employee_details'=> [],
        'layout_type'     => '0',
        'group_image'     => '',
        'group_caption'   => '',
        'regards_text'    => 'Best Regards,',
    ];
    $o = array_merge($defaults, $opts);
    extract($o);

    $font  = '"Proxima Nova RG","Proxima Nova",Arial,sans-serif';
    $w     = 750;
    $body  = processContentForOutlook($body_content);

    $tpl = '<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<style>
body,table,td,p{font-family:' . $font . ';}
img{border:0;height:auto;line-height:100%;outline:none;text-decoration:none;}
p{margin-top:0;margin-bottom:12pt;line-height:1.5;}
</style>
</head>
<body style="margin:0;padding:0;background:#fff;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" width="' . $w . '" style="width:' . $w . 'px;max-width:100%;">';

    if ($header_image) {
        $tpl .= '<tr><td><img src="' . $header_image . '" width="' . $w . '" alt="Header" style="display:block;width:' . $w . 'px;max-width:100%;"/></td></tr>';
    }

    $tpl .= '<tr><td style="padding:30px 50px;font-family:' . $font . ';line-height:1.5;">' . $body . '</td></tr>';

    if ($layout_type === 'group' && !empty($group_image)) {
        $tpl .= generateGroupSection($group_image, $group_caption, $font, $w);
    } elseif (!empty($employee_images)) {
        $tpl .= generateEmployeeGrid($employee_images, $employee_details, $font, $w);
    }

    $tpl .= '<tr><td style="padding:10px 50px;font-family:' . $font . ';">
<p style="margin:0 0 6px;color:#333;"><strong>' . htmlspecialchars($regards_text) . '</strong></p>
<p style="margin:0;color:#333;"><strong>' . htmlspecialchars($signature_name) . '</strong><br>
<span style="color:#666;">' . htmlspecialchars($signature_title) . '</span></p>
</td></tr>
<tr><td style="background:#000;padding:15px;text-align:center;color:#fff;font-family:' . $font . ';font-size:13px;">
Padak (Pvt) Ltd, Batticaloa, Sri Lanka — +94 710815522
</td></tr>
</table></body></html>';

    return $tpl;
}

function generateGroupSection(string $img, string $cap, string $font, int $w): string {
    $iw = 600;
    $h  = '<tr><td align="center" style="padding:20px 0;"><div style="text-align:center;max-width:' . $iw . 'px;margin:0 auto;">';
    $h .= '<img src="' . $img . '" width="' . $iw . '" style="max-width:100%;display:block;border-radius:5px;" alt="Group"/>';
    if ($cap) $h .= '<div style="padding-top:12px;font-style:italic;font-size:14px;font-family:' . $font . ';">' . htmlspecialchars($cap) . '</div>';
    $h .= '</div></td></tr>';
    return $h;
}

function generateEmployeeGrid(array $imgs, array $dets, string $font, int $w): string {
    $filtered = [];
    foreach ($imgs as $i => $img) {
        if (!empty($img)) $filtered[] = ['img' => $img, 'det' => $dets[$i] ?? ['name'=>'','title'=>'']];
    }
    if (!$filtered) return '';

    $cell = function(array $item) use ($font): string {
        $h  = '<td align="center" style="padding:10px;vertical-align:top;">';
        $h .= '<img src="' . $item['img'] . '" width="200" height="200" style="display:block;border-radius:5px;object-fit:cover;" alt=""/>';
        if (!empty($item['det']['name'])) {
            $h .= '<div style="padding-top:10px;font-family:' . $font . ';">';
            $h .= '<strong style="font-size:15px;">' . htmlspecialchars($item['det']['name']) . '</strong><br>';
            $h .= '<span style="font-size:13px;color:#666;">' . htmlspecialchars($item['det']['title']) . '</span>';
            $h .= '</div>';
        }
        $h .= '</td>';
        return $h;
    };

    $count = count($filtered);
    $cols  = $count <= 2 ? $count : 3;
    $html  = '<tr><td><table cellpadding="0" cellspacing="0" border="0" align="center" width="100%">';
    $chunks = array_chunk($filtered, $cols);
    foreach ($chunks as $row) {
        $html .= '<tr>';
        foreach ($row as $item) $html .= $cell($item);
        // pad incomplete row
        $pad = $cols - count($row);
        for ($p = 0; $p < $pad; $p++) $html .= '<td></td>';
        $html .= '</tr>';
    }
    $html .= '</table></td></tr>';
    return $html;
}

function processContentForOutlook(string $html): string {
    if (!$html) return '';
    $font = '"Proxima Nova RG","Proxima Nova",Arial,sans-serif';
    $dom  = new DOMDocument();
    libxml_use_internal_errors(true);
    @$dom->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);
    foreach ($xpath->query('//*') as $el) {
        $styles = [];
        foreach (explode(';', $el->getAttribute('style')) as $s) {
            $s = trim($s); if (!$s) continue;
            [$k, $v] = array_pad(explode(':', $s, 2), 2, '');
            if ($k && $v) $styles[trim($k)] = trim($v);
        }
        $styles['font-family'] = $font;
        $el->setAttribute('style', implode(';', array_map(fn($k,$v)=>"$k:$v", array_keys($styles), $styles)));
    }
    $out = $dom->saveHTML();
    return preg_replace(['/<\?xml[^>]+>/i','/<\/?html[^>]*>/i','/<\/?head[^>]*>/i','/<\/?body[^>]*>/i'], '', $out);
}

// ─── Render page ──────────────────────────────────────────────────────────────
renderLayout('Email Template Generator', 'email_template');
?>

<style>
/* ── Inherit all CRM variables; no hardcoded colours ── */

/* Stepper */
.etg-stepper{display:flex;position:relative;margin-bottom:28px;counter-reset:step}
.etg-step{flex:1;text-align:center;position:relative}
.etg-step:not(:last-child)::after{content:'';position:absolute;top:17px;left:50%;width:100%;height:2px;background:var(--border);z-index:0;transition:background .3s}
.etg-step.done:not(:last-child)::after,.etg-step.active:not(:last-child)::after{background:var(--orange)}
.etg-step-circle{width:34px;height:34px;border-radius:50%;background:var(--bg4);border:2px solid var(--border);color:var(--text3);display:flex;align-items:center;justify-content:center;margin:0 auto 6px;position:relative;z-index:1;font-size:13px;font-weight:700;transition:all .3s}
.etg-step.active .etg-step-circle{background:var(--orange);border-color:var(--orange);color:#fff;box-shadow:0 0 0 4px var(--orange-bg)}
.etg-step.done .etg-step-circle{background:var(--green);border-color:var(--green);color:#fff}
.etg-step-label{font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase;letter-spacing:.05em}
.etg-step.active .etg-step-label{color:var(--orange)}
.etg-step.done .etg-step-label{color:var(--green)}

/* Form cards */
.etg-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);margin-bottom:16px;overflow:hidden;transition:background .2s,border-color .2s}
.etg-card-hd{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border);background:var(--bg3)}
.etg-card-hd-icon{font-size:18px;flex-shrink:0}
.etg-card-hd h3{font-size:14px;font-weight:700;margin:0;font-family:var(--font-display,'inherit')}
.etg-card-bd{padding:18px}

/* File drop zone */
.etg-drop{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:28px 16px;border:2px dashed var(--border);border-radius:var(--radius);background:var(--bg3);cursor:pointer;transition:border-color .2s,background .2s;text-align:center;position:relative}
.etg-drop:hover,.etg-drop.dragover{border-color:var(--orange);background:var(--orange-bg)}
.etg-drop input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.etg-drop-icon{font-size:28px}
.etg-drop-hint{font-size:12px;color:var(--text3)}
.etg-file-pill{display:inline-flex;align-items:center;gap:6px;margin-top:10px;padding:5px 12px;background:var(--bg4);border:1px solid var(--border);border-radius:99px;font-size:12px;color:var(--text2);max-width:100%;overflow:hidden}
.etg-file-pill span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

/* Layout grid picker */
.etg-layouts{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px}
.etg-layout-opt{position:relative}
.etg-layout-opt input{position:absolute;opacity:0;width:0;height:0}
.etg-layout-lbl{display:flex;flex-direction:column;align-items:center;gap:8px;padding:12px 8px;border:2px solid var(--border);border-radius:var(--radius);background:var(--bg3);cursor:pointer;transition:all .2s}
.etg-layout-lbl:hover{border-color:var(--orange);background:var(--orange-bg)}
.etg-layout-opt input:checked + .etg-layout-lbl{border-color:var(--orange);background:var(--orange-bg)}
.etg-layout-vis{width:64px;height:48px;background:var(--bg4);border-radius:6px;display:flex;align-items:center;justify-content:center;padding:6px;gap:3px;flex-wrap:wrap}
.etg-dot{background:var(--text3);border-radius:3px;flex:1;min-width:12px;min-height:100%;transition:background .2s}
.etg-layout-opt input:checked + .etg-layout-lbl .etg-dot{background:var(--orange)}
.etg-layout-name{font-size:11px;font-weight:600;color:var(--text2);text-align:center}

/* Employee sub-cards */
.etg-emp-grid{display:grid;gap:12px;margin-top:14px}

/* Two-column layout */
.etg-cols{display:grid;grid-template-columns:1fr 1fr;gap:24px}
@media(max-width:900px){.etg-cols{grid-template-columns:1fr}}

/* Preview pane */
.etg-preview-wrap{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;height:fit-content;position:sticky;top:calc(var(--header-h) + 16px)}
.etg-preview-bar{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--border);background:var(--bg3);gap:10px;flex-wrap:wrap}
.etg-preview-body{padding:18px;max-height:calc(100vh - 200px);overflow-y:auto}
.etg-preview-email{background:#fff;border-radius:var(--radius);overflow:hidden;border:1px solid var(--border2)}
.etg-preview-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:60px 20px;color:var(--text3);text-align:center}
.etg-preview-empty .big-icon{font-size:48px}

/* Buttons row */
.etg-actions{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:6px}

/* Error banner */
.etg-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:var(--radius-sm);padding:12px 16px;color:var(--red);font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px}

/* Textarea wrapper needed for TinyMCE theme swap */
#rich-text-editor{width:100%;min-height:240px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px;color:var(--text);font-size:13.5px;resize:vertical}

/* Loading overlay */
.etg-loading{display:none;position:fixed;inset:0;background:var(--modal-overlay);z-index:9000;align-items:center;justify-content:center;flex-direction:column;gap:16px}
.etg-loading.show{display:flex}
.etg-spinner{width:44px;height:44px;border:4px solid var(--border2);border-top-color:var(--orange);border-radius:50%;animation:etgSpin .8s linear infinite}
@keyframes etgSpin{to{transform:rotate(360deg)}}
.etg-loading p{color:#fff;font-size:14px;font-weight:600}
</style>

<!-- Loading overlay -->
<div id="etg-loading" class="etg-loading">
  <div class="etg-spinner"></div>
  <p>Generating template…</p>
</div>

<div class="etg-cols">
  <!-- ─── LEFT: Form ─────────────────────────────────────────────────── -->
  <div>
    <!-- Stepper -->
    <div class="etg-stepper" id="etg-stepper">
      <div class="etg-step active" data-step="1">
        <div class="etg-step-circle">1</div>
        <div class="etg-step-label">Content</div>
      </div>
      <div class="etg-step" data-step="2">
        <div class="etg-step-circle">2</div>
        <div class="etg-step-label">Images</div>
      </div>
      <div class="etg-step" data-step="3">
        <div class="etg-step-circle">3</div>
        <div class="etg-step-label">Signature</div>
      </div>
      <div class="etg-step" data-step="4">
        <div class="etg-step-circle">4</div>
        <div class="etg-step-label">Generate</div>
      </div>
    </div>

    <?php if ($form_error): ?>
    <div class="etg-error">⚠ <?= h($form_error) ?></div>
    <?php endif; ?>

    <form id="etg-form" method="post" enctype="multipart/form-data">

      <!-- Header image -->
      <div class="etg-card">
        <div class="etg-card-hd"><span class="etg-card-hd-icon">🖼️</span><h3>Header Image</h3></div>
        <div class="etg-card-bd">
          <div class="etg-drop" id="drop-header">
            <span class="etg-drop-icon">☁️</span>
            <strong style="font-size:13px;color:var(--text)">Drop header image or click to browse</strong>
            <span class="etg-drop-hint">Recommended: 750px wide · JPG / PNG / GIF</span>
            <input type="file" name="header_image" accept="image/*" id="fi-header" onchange="showFilePill(this,'pill-header')">
          </div>
          <div id="pill-header"></div>
        </div>
      </div>

      <!-- Email body -->
      <div class="etg-card">
        <div class="etg-card-hd"><span class="etg-card-hd-icon">✍️</span><h3>Email Content</h3></div>
        <div class="etg-card-bd">
          <textarea id="rich-text-editor" name="body_content"><?= isset($_POST['body_content']) ? h($_POST['body_content']) : '' ?></textarea>
          <p style="margin:8px 0 0;font-size:11px;color:var(--text3)">ℹ️ Dates and keywords will be highlighted automatically in the output</p>
        </div>
      </div>

      <!-- Layout picker -->
      <div class="etg-card">
        <div class="etg-card-hd"><span class="etg-card-hd-icon">⊞</span><h3>Photo Layout</h3></div>
        <div class="etg-card-bd">
          <div class="etg-layouts">
            <?php
            $layouts = [
              ['0',     'No Photos',  ''],
              ['group', 'Group',      'group'],
              ['1',     'Single',     '1'],
              ['2',     '1 × 2',      '1-1'],
              ['3',     '1 × 3',      '1-1-1'],
              ['2-2',   '2 × 2',      '2-2'],
              ['3-2',   '3 + 2',      '3-2'],
              ['3-3',   '3 × 3',      '3-3'],
            ];
            foreach ($layouts as [$val, $label, $vis]):
              $checked = (($_POST['layout_type'] ?? '0') === $val) ? 'checked' : '';
            ?>
            <div class="etg-layout-opt">
              <input type="radio" name="layout_type" value="<?= $val ?>" id="lt-<?= $val ?>" <?= $checked ?> onchange="buildImageFields(this.value)">
              <label for="lt-<?= $val ?>" class="etg-layout-lbl">
                <div class="etg-layout-vis" id="vis-<?= $val ?>">
                  <?php if ($val === '0'): ?>
                    <span style="font-size:20px;color:var(--text3)">—</span>
                  <?php elseif ($val === 'group'): ?>
                    <span style="font-size:20px">👥</span>
                  <?php else: ?>
                    <?php
                    $dotMap = ['1'=>[1],'1-1'=>[1,1],'1-1-1'=>[1,1,1],'2-2'=>[2,2],'3-2'=>[3,2],'3-3'=>[3,3,3]];
                    $rows   = $dotMap[$vis] ?? [1];
                    foreach ($rows as $n): for ($d=0; $d<$n; $d++): ?>
                    <div class="etg-dot" style="height:<?= count($rows)===1?'100%':'40%' ?>;min-width:<?= $n===1?'80%':'26%' ?>;"></div>
                    <?php endfor; endforeach; ?>
                  <?php endif; ?>
                </div>
                <span class="etg-layout-name"><?= $label ?></span>
              </label>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Dynamic employee / group image fields -->
          <div id="etg-img-fields" class="etg-emp-grid"></div>
        </div>
      </div>

      <!-- Signature -->
      <div class="etg-card">
        <div class="etg-card-hd"><span class="etg-card-hd-icon">✒️</span><h3>Signature</h3></div>
        <div class="etg-card-bd">
          <div class="form-group">
            <label class="form-label">Closing phrase</label>
            <input type="text" name="regards_text" class="form-control" placeholder="Best Regards," value="<?= h($_POST['regards_text'] ?? 'Best Regards,') ?>">
          </div>
          <div class="form-row">
            <div class="form-group" style="margin:0">
              <label class="form-label">Name</label>
              <input type="text" name="regards_name" class="form-control" placeholder="Full name" value="<?= h($_POST['regards_name'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label">Title / Position</label>
              <input type="text" name="regards_title" class="form-control" placeholder="e.g. Project Manager" value="<?= h($_POST['regards_title'] ?? '') ?>">
            </div>
          </div>
        </div>
      </div>

      <div class="etg-actions">
        <button type="button" class="btn btn-ghost" onclick="resetForm()">
          🔄 Reset
        </button>
        <button type="submit" class="btn btn-primary" id="etg-submit">
          ✉️ Generate Template
        </button>
      </div>
    </form>
  </div>

  <!-- ─── RIGHT: Preview ────────────────────────────────────────────── -->
  <div>
    <div class="etg-preview-wrap">
      <div class="etg-preview-bar">
        <strong style="font-size:14px;font-family:var(--font-display)">📧 Preview</strong>
        <?php if ($preview_html): ?>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <button class="btn btn-ghost btn-sm" onclick="copyEmail('outlook')">📋 Copy for Outlook</button>
          <button class="btn btn-primary btn-sm" onclick="copyEmail('gmail')">📨 Copy for Gmail</button>
        </div>
        <?php endif; ?>
      </div>
      <div class="etg-preview-body">
        <?php if ($preview_html): ?>
          <div class="etg-preview-email" id="etg-preview-content">
            <?= $preview_html ?>
          </div>
        <?php else: ?>
          <div class="etg-preview-empty">
            <div class="big-icon">✉️</div>
            <strong style="color:var(--text2)">Preview will appear here</strong>
            <span style="font-size:12px">Fill in the form and click Generate</span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.7.0/tinymce.min.js" referrerpolicy="origin"></script>
<script>
/* ── Globals ─────────────────────────────────────────────────── */
let tinyMCEReady = false;

/* ── TinyMCE init (theme-aware) ──────────────────────────────── */
function getTmceTheme() {
  return document.documentElement.getAttribute('data-theme') === 'light'
    ? ['oxide', '']
    : ['oxide-dark', 'dark'];
}

function initTinyMCE() {
  if (typeof tinymce === 'undefined') return;
  const [skin, content_css] = getTmceTheme();
  tinymce.init({
    selector: '#rich-text-editor',
    height: 280,
    menubar: false,
    plugins: 'code lists link table',
    toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | code',
    skin,
    content_css,
    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
    setup(ed) {
      ed.on('init', () => { tinyMCEReady = true; });
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initTinyMCE();

  // Init image fields from checked layout (for page reload after POST)
  const checked = document.querySelector('input[name="layout_type"]:checked');
  if (checked && checked.value !== '0') buildImageFields(checked.value);

  // Update stepper if template was generated
  <?php if ($preview_html): ?>
  setStep(4);
  <?php elseif (!empty($_POST)): ?>
  setStep(2);
  <?php endif; ?>

  // File drop-zone drag styling
  document.querySelectorAll('.etg-drop').forEach(zone => {
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => { e.preventDefault(); zone.classList.remove('dragover'); });
  });
});

// Re-apply TinyMCE skin when CRM theme changes
document.addEventListener('themeChanged', () => {
  if (typeof tinymce !== 'undefined') {
    tinymce.remove('#rich-text-editor');
  }
  tinyMCEReady = false;
  initTinyMCE();
});

/* ── Stepper ─────────────────────────────────────────────────── */
function setStep(n) {
  document.querySelectorAll('.etg-step').forEach(el => {
    const s = parseInt(el.dataset.step);
    el.classList.toggle('active', s === n);
    el.classList.toggle('done', s < n);
    if (s < n) el.querySelector('.etg-step-circle').textContent = '✓';
    else el.querySelector('.etg-step-circle').textContent = s;
  });
}

/* ── File pill helper ────────────────────────────────────────── */
function showFilePill(input, pillId) {
  const pill = document.getElementById(pillId);
  if (!pill) return;
  if (input.files.length) {
    pill.innerHTML = `<div class="etg-file-pill">📎 <span>${input.files[0].name}</span></div>`;
  } else {
    pill.innerHTML = '';
  }
}

/* ── Dynamic image fields ────────────────────────────────────── */
function buildImageFields(layout) {
  const container = document.getElementById('etg-img-fields');
  container.innerHTML = '';
  if (layout === '0') return;

  if (layout === 'group') {
    container.innerHTML = `
      <div class="etg-card" style="margin:0">
        <div class="etg-card-hd"><span class="etg-card-hd-icon">👥</span><h3>Group Photo</h3></div>
        <div class="etg-card-bd">
          <div class="etg-drop" id="drop-group">
            <span class="etg-drop-icon">🖼️</span>
            <strong style="font-size:13px;color:var(--text)">Upload group photo</strong>
            <span class="etg-drop-hint">Recommended: wide landscape image</span>
            <input type="file" name="group_image" accept="image/*" onchange="showFilePill(this,'pill-group')">
          </div>
          <div id="pill-group"></div>
          <div class="form-group" style="margin-top:12px">
            <label class="form-label">Caption (optional)</label>
            <input type="text" name="group_caption" class="form-control" placeholder="e.g. The entire team at our 2024 retreat">
          </div>
        </div>
      </div>`;
    return;
  }

  const countMap = {'1':1,'2':2,'3':3,'2-2':4,'3-2':5,'3-3':9};
  const count    = countMap[layout] || 0;
  for (let i = 1; i <= count; i++) {
    const div = document.createElement('div');
    div.className = 'etg-card';
    div.style.margin = '0';
    div.innerHTML = `
      <div class="etg-card-hd"><span class="etg-card-hd-icon">👤</span><h3>Employee ${i}</h3></div>
      <div class="etg-card-bd">
        <div class="etg-drop">
          <span class="etg-drop-icon">📷</span>
          <strong style="font-size:13px;color:var(--text)">Upload photo</strong>
          <span class="etg-drop-hint">Square crop recommended · 200×200px</span>
          <input type="file" name="employee_image_${i}" accept="image/*" onchange="showFilePill(this,'emp-pill-${i}')">
        </div>
        <div id="emp-pill-${i}"></div>
        <div class="form-row" style="margin-top:12px">
          <div class="form-group" style="margin:0">
            <label class="form-label">Name</label>
            <input type="text" name="employee_name_${i}" class="form-control" placeholder="Employee name">
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Title</label>
            <input type="text" name="employee_title_${i}" class="form-control" placeholder="Job title">
          </div>
        </div>
      </div>`;
    container.appendChild(div);
  }

  // Update stepper
  setStep(2);
}

/* ── Reset form ─────────────────────────────────────────────── */
function resetForm() {
  if (!confirm('Reset the form? All inputs and the preview will be cleared.')) return;

  // Clear TinyMCE
  if (typeof tinymce !== 'undefined') {
    const ed = tinymce.get('rich-text-editor');
    if (ed) ed.setContent('');
  }

  // Reset native form fields
  const form = document.getElementById('etg-form');
  form.reset();

  // Clear file pills
  document.querySelectorAll('[id^=pill-],[id^=emp-pill-]').forEach(el => el.innerHTML = '');

  // Reset layout to "No Photos"
  const defLayout = document.getElementById('lt-0');
  if (defLayout) defLayout.checked = true;
  buildImageFields('0');

  // Clear dynamic image fields
  document.getElementById('etg-img-fields').innerHTML = '';

  // Reset stepper
  setStep(1);

  toast('Form reset successfully', 'success');
}

/* ── Form submit — loading overlay ──────────────────────────── */
document.getElementById('etg-form').addEventListener('submit', function (e) {
  // Sync TinyMCE content into the textarea before submit
  if (typeof tinymce !== 'undefined') {
    const ed = tinymce.get('rich-text-editor');
    if (ed) ed.save();
  }

  const body = (document.querySelector('textarea[name=body_content]') || {}).value || '';
  const tmce = typeof tinymce !== 'undefined' && tinymce.get('rich-text-editor')
    ? tinymce.get('rich-text-editor').getContent()
    : '';

  if (!body.trim() && !tmce.trim()) {
    e.preventDefault();
    toast('Please add email content before generating.', 'error');
    return;
  }

  document.getElementById('etg-loading').classList.add('show');
  setStep(4);
});

/* ── Copy for Outlook / Gmail ───────────────────────────────── */
async function copyEmail(target) {
  const el = document.getElementById('etg-preview-content');
  if (!el) { toast('Nothing to copy', 'error'); return; }

  // Modern clipboard API
  if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
    try {
      const htmlBlob = new Blob([el.innerHTML], { type: 'text/html' });
      const txtBlob  = new Blob([el.innerText],  { type: 'text/plain' });
      await navigator.clipboard.write([new ClipboardItem({ 'text/html': htmlBlob, 'text/plain': txtBlob })]);
      toast(`Copied! Paste into ${target === 'gmail' ? 'Gmail' : 'Outlook'} compose window.`, 'success');
      return;
    } catch (err) {
      console.warn('Clipboard API error', err);
    }
  }

  // Fallback: select + execCommand
  try {
    const range = document.createRange();
    range.selectNodeContents(el);
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
    document.execCommand('copy');
    sel.removeAllRanges();
    toast(`Copied for ${target === 'gmail' ? 'Gmail' : 'Outlook'}!`, 'success');
  } catch (err) {
    toast('Could not copy — please select and copy manually.', 'error');
  }
}
</script>

<?php renderLayoutEnd(); ?>