<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$user = currentUser();

// ── UPLOAD DIR ──
define('EMAIL_UPLOAD_DIR', __DIR__ . '/uploads/email_headers/');
if (!is_dir(EMAIL_UPLOAD_DIR)) mkdir(EMAIL_UPLOAD_DIR, 0755, true);

// ── HELPERS ──
function getBase64Image(string $path): string {
    if (!file_exists($path)) return '';
    $data = file_get_contents($path);
    if (!$data) return '';
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $path);
    finfo_close($finfo);
    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

function saveUpload(string $key, int $idx = 0): string {
    $field = $idx > 0 ? $key . '_' . $idx : $key;
    $f = $_FILES[$field] ?? null;
    if (!$f || $f['error'] !== 0) return '';
    if (!getimagesize($f['tmp_name'])) return '';
    $ext   = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $fname = EMAIL_UPLOAD_DIR . uniqid('et_') . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $fname)) return '';
    return $fname;
}

// ── POST HANDLER ──
$preview_html = '';
$message = '';
$posted = [];  // carry form values back

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate') {
    // Carry posted values
    $posted = [
        'body_content'   => $_POST['body_content']   ?? '',
        'regards_text'   => $_POST['regards_text']   ?? 'Best Regards,',
        'regards_name'   => $_POST['regards_name']   ?? '',
        'regards_title'  => $_POST['regards_title']  ?? '',
        'layout_type'    => $_POST['layout_type']    ?? '0',
        'group_caption'  => $_POST['group_caption']  ?? '',
    ];

    if (!$posted['body_content']) {
        $message = 'error:Please add email content before generating.';
    } else {
        // Header image
        $header_path = saveUpload('header_image');
        $header_b64  = $header_path ? getBase64Image($header_path) : '';
        if ($header_path) @unlink($header_path);

        $layout = $posted['layout_type'];
        $employee_images  = [];
        $employee_details = [];
        $group_b64 = '';

        if ($layout === 'group') {
            $gp = saveUpload('group_image');
            $group_b64 = $gp ? getBase64Image($gp) : '';
            if ($gp) @unlink($gp);
        } else {
            $max = match($layout) { '1'=>1,'2'=>2,'3'=>3,'2-2'=>4,'3-2'=>5,'3-3'=>9,default=>0 };
            for ($i = 1; $i <= $max; $i++) {
                $ep = saveUpload('employee_image', $i);
                $employee_images[]  = $ep ? getBase64Image($ep) : '';
                if ($ep) @unlink($ep);
                $employee_details[] = [
                    'name'  => trim($_POST['employee_name_'.$i]  ?? ''),
                    'title' => trim($_POST['employee_title_'.$i] ?? ''),
                ];
            }
        }

        $preview_html = generateEmailTemplate([
            'header_image'    => $header_b64,
            'body_content'    => $posted['body_content'],
            'signature_name'  => $posted['regards_name'],
            'signature_title' => $posted['regards_title'],
            'regards_text'    => $posted['regards_text'],
            'layout_type'     => $layout,
            'employee_images' => $employee_images,
            'employee_details'=> $employee_details,
            'group_image'     => $group_b64,
            'group_caption'   => $posted['group_caption'],
        ]);
        $message = 'success:Email template generated successfully!';
        logActivity('generated email template', 'email', 0);
    }
}

// ── EMAIL GENERATION FUNCTIONS (ported from standalone) ──

function processContentForOutlook(string $html): string {
    if (!$html) return '';
    $font = '"Proxima Nova RG","Proxima Nova",Arial,sans-serif';
    $dom  = new DOMDocument();
    libxml_use_internal_errors(true);
    @$dom->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);
    $all   = $xpath->query('//*');
    if ($all) {
        foreach ($all as $el) {
            $tag   = strtolower($el->nodeName);
            $style = $el->getAttribute('style');
            $styles = [];
            foreach (explode(';', $style) as $s) {
                $s = trim($s); if (!$s) continue;
                $p = explode(':', $s, 2);
                if (count($p) === 2) $styles[trim(strtolower($p[0]))] = trim($p[1]);
            }
            $styles['font-family'] = $font . ' !important';
            $textTags = ['p','div','span','strong','em','h1','h2','h3','h4','h5','h6','td','th','a','li'];
            if (in_array($tag, $textTags)) {
                $lh = $styles['line-height'] ?? null;
                if (!$lh) { $styles['line-height'] = '1.5 !important'; $lhv = '1.5'; }
                else { $lhv = preg_replace('/\s*!important\s*/','', $lh); $styles['line-height'] = $lhv . ' !important'; }
                $styles['mso-line-height-rule'] = 'exactly';
                if (is_numeric($lhv)) $styles['mso-line-height-alt'] = (floatval($lhv)*16) . 'pt';
                if ($tag === 'p') { $styles['margin-top'] ??= '0'; $styles['margin-bottom'] ??= '12pt'; }
            }
            $ns = ''; foreach ($styles as $k=>$v) $ns .= $k.':'.$v.';';
            $el->setAttribute('style', $ns);
            if ($tag === 'font') $el->setAttribute('face', $font);
        }
    }
    $out = $dom->saveHTML();
    $out = preg_replace(['/<\?xml[^>]+\?>$/m','/<\/?html[^>]*>/i','/<\/?head[^>]*>/i','/<\/?body[^>]*>/i'], '', $out);
    return trim($out);
}

function generateGroupImage(string $img, string $caption, string $font, int $w): string {
    if (!$img) return '';
    $iw   = 600;
    $html = '<tr><td align="center" style="padding:20px 0;font-family:'.$font.' !important;" width="'.$w.'">';
    $html .= '<div style="text-align:center;max-width:'.$iw.'px;margin:0 auto;font-family:'.$font.' !important;">';
    $html .= '<img src="'.$img.'" width="'.$iw.'" style="width:'.$iw.'px;max-width:100%;display:block;border:0;margin:0 auto;border-radius:5px;"/>';
    if ($caption) $html .= '<div style="padding-top:15px;font-family:'.$font.' !important;"><span style="font-style:italic;font-size:14px;font-family:'.$font.' !important;">'.htmlspecialchars($caption).'</span></div>';
    $html .= '</div></td></tr>';
    return $html;
}

function generateImageGrid(array $images, string $layout, array $details, string $font, int $w): string {
    $valid = array_values(array_filter($images));
    $pos   = [];
    foreach ($images as $i => $v) if ($v) $pos[] = $i;
    if (!$valid) return '';

    $cell = function(int $idx, string $img, array $det, int $cw) use ($font): string {
        $h  = '<td align="center" style="padding:10px;width:'.$cw.'px;font-family:'.$font.' !important;">';
        $h .= '<div style="text-align:center;font-family:'.$font.' !important;">';
        if ($img) $h .= '<img src="'.$img.'" width="200" height="200" style="display:block;border:0;margin:0 auto;border-radius:5px;object-fit:cover;"/><div style="height:10px;line-height:10px;font-size:10px;">&nbsp;</div>';
        if (!empty($det[$idx]['name'])) {
            $h .= '<div style="padding-top:10px;font-family:'.$font.' !important;">';
            $h .= '<span style="font-weight:700;font-size:15px;font-family:'.$font.' !important;">'.htmlspecialchars($det[$idx]['name']).'</span><br>';
            $h .= '<span style="font-size:13px;color:#555;font-family:'.$font.' !important;">'.htmlspecialchars($det[$idx]['title'] ?? '').'</span>';
            $h .= '</div>';
        }
        return $h . '</div></td>';
    };

    $row  = fn(array $idxs) => '<tr><td align="center"><table cellpadding="0" cellspacing="0" border="0"><tr>' .
        implode('', array_map(fn($i) => $cell($pos[$i], $valid[$i], $details, 200), $idxs)) .
        '</tr></table></td></tr>';

    $n    = count($valid);
    $html = '<tr><td align="center" width="'.$w.'" style="font-family:'.$font.' !important;">';
    $html .= '<table cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="width:100%;border-collapse:collapse;font-family:'.$font.' !important;">';

    if ($n <= 3) {
        $html .= $row(range(0, $n-1));
    } elseif ($n === 4) {
        $html .= $row([0,1]) . $row([2,3]);
    } elseif ($n === 5) {
        $html .= $row([0,1,2]);
        $html .= '<tr><td align="center"><table cellpadding="0" cellspacing="0" border="0"><tr>';
        $html .= '<td width="100" style="font-family:'.$font.' !important;">&nbsp;</td>';
        $html .= $cell($pos[3],$valid[3],$details,200) . $cell($pos[4],$valid[4],$details,200);
        $html .= '<td width="100" style="font-family:'.$font.' !important;">&nbsp;</td>';
        $html .= '</tr></table></td></tr>';
    } else {
        for ($r = 0; $r < ceil($n/3); $r++) {
            $idxs = array_filter(range($r*3, $r*3+2), fn($i) => $i < $n);
            $html .= $row(array_values($idxs));
        }
    }
    return $html . '</table></td></tr>';
}

function generateEmailTemplate(array $opts): string {
    $d = ['header_image'=>'','body_content'=>'','signature_name'=>'','signature_title'=>'',
          'employee_images'=>[],'layout_type'=>'0','employee_details'=>[],'group_image'=>'',
          'group_caption'=>'','regards_text'=>'Best Regards,'];
    $opts = array_merge($d, $opts);
    extract($opts);

    $font = '"Proxima Nova RG","Proxima Nova",Arial,sans-serif';
    $W    = 750;
    $body = processContentForOutlook($body_content);

    $mso_styles = "body,table,td,p,a,li,blockquote{font-family:{$font}!important;mso-line-height-rule:exactly}
    p{mso-margin-top-alt:0;mso-margin-bottom-alt:12.0pt;margin:0 0 12pt 0}
    .MsoNormal{margin:0 0 12pt 0;line-height:115%;font-size:11pt;font-family:{$font}}
    table{mso-table-lspace:0pt;mso-table-rspace:0pt}";

    $t  = '<!DOCTYPE html><html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:v="urn:schemas-microsoft-com:vml">';
    $t .= '<head><meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>';
    $t .= '<meta name="viewport" content="width=device-width,initial-scale=1.0"/>';
    $t .= '<!--[if mso]><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>';
    $t .= '<style>'.$mso_styles.'</style><![endif]-->';
    $t .= '<style>body{margin:0;padding:0;font-family:'.$font.' !important}table,td{border-collapse:collapse;font-family:'.$font.' !important}img{border:0;display:block}p{margin:0 0 12pt 0;line-height:1.5}</style>';
    $t .= '</head>';
    $t .= '<body style="margin:0;padding:0;background:#fff;font-family:'.$font.' !important;">';
    $t .= '<!--[if mso]><table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" width="'.$W.'"><tr><td><![endif]-->';
    $t .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" width="'.$W.'" style="width:'.$W.'px;max-width:100%;border-collapse:collapse;margin:0 auto;font-family:'.$font.' !important;">';

    // Header image
    $t .= '<tr><td align="center" style="padding:0;font-family:'.$font.' !important;" width="'.$W.'">';
    if ($header_image) $t .= '<img src="'.$header_image.'" alt="Header" width="'.$W.'" style="width:'.$W.'px;max-width:100%;display:block;border:0;margin:0 auto;"/>';
    $t .= '</td></tr>';

    // Body content
    $t .= '<tr><td class="outlook-content-cell" align="left" style="padding:30px 50px;font-family:'.$font.' !important;" width="'.$W.'">';
    $t .= '<div style="width:100%;font-family:'.$font.' !important;line-height:1.5 !important;mso-line-height-rule:exactly;">'.$body.'</div>';
    $t .= '</td></tr>';

    // Images
    if ($layout_type === 'group' && $group_image)
        $t .= generateGroupImage($group_image, $group_caption, $font, $W);
    elseif (!empty($employee_images))
        $t .= generateImageGrid($employee_images, $layout_type, $employee_details, $font, $W);

    // Signature
    $t .= '<tr><td height="5" style="font-size:5px;line-height:5px;">&nbsp;</td></tr>';
    $t .= '<tr><td align="left" style="padding:10px 50px;font-family:'.$font.' !important;" width="'.$W.'">';
    $t .= '<p style="margin:0 0 8pt 0;line-height:1.5;font-family:'.$font.' !important;"><strong>'.htmlspecialchars($regards_text).'</strong></p>';
    $t .= '<p style="margin:0;line-height:1.6;font-family:'.$font.' !important;">';
    $t .= '<strong>'.htmlspecialchars($signature_name).'</strong>';
    if ($signature_title) $t .= '<br><span style="color:#666;font-family:'.$font.' !important;">'.htmlspecialchars($signature_title).'</span>';
    $t .= '</p></td></tr>';

    // Footer
    $t .= '<tr><td height="5" style="font-size:5px;line-height:5px;">&nbsp;</td></tr>';
    $t .= '<tr><td align="center" style="background:#000;padding:15px;font-family:'.$font.' !important;" width="'.$W.'">';
    $t .= '<span style="color:#fff;font-size:13px;font-family:'.$font.' !important;">Padak (Pvt) Ltd, Batticaloa, Sri Lanka &nbsp;|&nbsp; +94 710815522</span>';
    $t .= '</td></tr>';
    $t .= '</table>';
    $t .= '<!--[if mso]></td></tr></table><![endif]-->';
    $t .= '</body></html>';
    return $t;
}

renderLayout('Email Template', 'email_template');
?>

<!-- Page-specific CDN: TinyMCE + Font Awesome (not in main layout) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js"></script>

<style>
/* ══ EMAIL TEMPLATE PAGE ══ */
.et-layout{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;min-height:calc(100vh - var(--header-h) - 64px)}
.et-form{display:flex;flex-direction:column;gap:14px;overflow-y:auto;padding-right:4px}
.et-preview{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;position:sticky;top:calc(var(--header-h) + 16px);max-height:calc(100vh - var(--header-h) - 80px);overflow-y:auto}

/* Steps */
.et-steps{display:flex;align-items:center;margin-bottom:4px;gap:0}
.et-step{display:flex;align-items:center;flex:1}
.et-step-circle{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;z-index:1;transition:all .2s}
.et-step-circle.done{background:var(--green);color:#fff}
.et-step-circle.active{background:var(--orange);color:#fff}
.et-step-circle.pending{background:var(--bg4);color:var(--text3)}
.et-step-label{font-size:11px;margin-left:6px;color:var(--text3);white-space:nowrap}
.et-step-label.active{color:var(--orange);font-weight:600}
.et-step-label.done{color:var(--green)}
.et-step-line{flex:1;height:2px;background:var(--border);margin:0 8px}
.et-step-line.done{background:var(--green)}

/* Layout selector */
.layout-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
.layout-opt{position:relative;cursor:pointer}
.layout-opt input{position:absolute;opacity:0;width:0;height:0}
.layout-opt-box{
  border:2px solid var(--border);border-radius:var(--radius);padding:10px 6px 8px;
  text-align:center;transition:all .15s;background:var(--bg3);
}
.layout-opt input:checked + .layout-opt-box{border-color:var(--orange);background:var(--orange-bg)}
.layout-opt-box:hover{border-color:var(--border2)}
.layout-opt-visual{
  height:44px;background:var(--bg4);border-radius:5px;margin-bottom:6px;
  display:flex;align-items:center;justify-content:center;gap:3px;padding:4px;
}
.lv-dot{background:var(--blue);border-radius:3px;flex:1;height:100%}
.layout-opt-label{font-size:10px;color:var(--text2);font-weight:600;line-height:1.2}

/* Employee upload card */
.emp-card{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:14px;margin-top:8px}
.emp-card-title{font-size:12px;font-weight:700;color:var(--text2);margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em}

/* File drop zone */
.file-drop{
  border:2px dashed var(--border);border-radius:var(--radius);padding:16px;
  text-align:center;cursor:pointer;transition:all .15s;background:var(--bg);
  position:relative;
}
.file-drop:hover,.file-drop.drag{border-color:var(--orange);background:var(--orange-bg)}
.file-drop input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.file-drop-icon{font-size:22px;margin-bottom:4px;color:var(--text3)}
.file-drop-text{font-size:12px;color:var(--text3)}
.file-drop-name{font-size:12px;color:var(--orange);margin-top:4px;font-weight:600}

/* Preview section */
.et-preview-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px}
.et-preview-title{font-size:14px;font-weight:700;color:var(--text);font-family:var(--font-display)}
.et-preview-actions{display:flex;gap:6px;flex-wrap:wrap}
.et-preview-empty{
  text-align:center;padding:48px 20px;color:var(--text3);
  background:var(--bg3);border-radius:var(--radius);
}
.et-preview-empty-icon{font-size:40px;margin-bottom:12px;opacity:.6}
#et-preview-frame{
  width:100%;border:1px solid var(--border);border-radius:var(--radius);
  background:#fff;overflow:hidden;
}

/* Copy toast */
.copy-toast{
  position:fixed;bottom:24px;left:50%;transform:translateX(-50%);
  background:var(--green);color:#fff;padding:8px 20px;border-radius:99px;
  font-size:13px;font-weight:600;z-index:999;
  opacity:0;transition:opacity .3s;pointer-events:none;
}
.copy-toast.show{opacity:1}

/* TinyMCE override for dark mode */
.tox-tinymce{border-radius:var(--radius-sm) !important;border-color:var(--border) !important}

@media(max-width:1100px){.et-layout{grid-template-columns:1fr}.et-preview{position:static;max-height:none}}
@media(max-width:600px){.layout-grid{grid-template-columns:repeat(2,1fr)}.et-steps{display:none}}
</style>

<?php
$layout_val  = $posted['layout_type'] ?? '0';
$mtype       = ['error'=>'error','success'=>'success'][explode(':',$message)[0] ?? ''] ?? '';
$mtext       = $message ? explode(':', $message, 2)[1] ?? '' : '';
?>

<?php if ($mtext): ?>
<script>document.addEventListener('DOMContentLoaded',function(){toast(<?=json_encode($mtext)?>,<?=json_encode($mtype)?>);});</script>
<?php endif; ?>

<div class="et-layout">

  <!-- ══ LEFT: FORM ══ -->
  <div class="et-form">

    <!-- Step indicator -->
    <div class="et-steps">
      <?php
      $steps = ['Content','Images','Signature','Generate'];
      $step  = $preview_html ? 4 : (($posted['layout_type']??'') && ($posted['layout_type']??'')!='0' ? 3 : ($posted['body_content']??'' ? 2 : 1));
      foreach ($steps as $i=>$sl):
        $n=$i+1;
        $cls = $n < $step ? 'done' : ($n==$step?'active':'pending');
        $lcls= $n < $step ? 'done' : ($n==$step?'active':'');
        if ($i>0): ?><div class="et-step-line <?= $n<=$step?'done':'' ?>"></div><?php endif; ?>
        <div class="et-step">
          <div class="et-step-circle <?= $cls ?>"><?= $n<$step?'✓':$n ?></div>
          <span class="et-step-label <?= $lcls ?>"><?= $sl ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <form method="POST" enctype="multipart/form-data" id="et-form">
      <input type="hidden" name="action" value="generate">

      <!-- ① Header Image -->
      <div class="card">
        <div class="card-header">
          <div class="card-title" style="display:flex;align-items:center;gap:8px">
            <span style="font-size:16px">🖼</span> Header Image
          </div>
        </div>
        <div class="file-drop" id="drop-header">
          <input type="file" name="header_image" accept="image/*" onchange="previewDrop(this,'drop-header')">
          <div class="file-drop-icon">☁</div>
          <div class="file-drop-text">Drop header image or click to browse<br><small>Recommended: 750px wide</small></div>
          <div class="file-drop-name" id="drop-header-name"></div>
        </div>
      </div>

      <!-- ② Email Content -->
      <div class="card">
        <div class="card-header">
          <div class="card-title" style="display:flex;align-items:center;gap:8px">
            <span style="font-size:16px">✍</span> Email Content *
          </div>
        </div>
        <textarea id="et-tinymce" name="body_content"><?= htmlspecialchars($posted['body_content'] ?? '') ?></textarea>
        <div style="font-size:11.5px;color:var(--text3);margin-top:6px">
          💡 Dates, times, IMPORTANT, URGENT keywords will be auto-bolded in the output.
        </div>
      </div>

      <!-- ③ Image Layout -->
      <div class="card">
        <div class="card-header">
          <div class="card-title" style="display:flex;align-items:center;gap:8px">
            <span style="font-size:16px">⊞</span> Image Layout
          </div>
        </div>
        <div class="layout-grid">
          <?php
          $layouts = [
            '0'   => ['No Images',        ''],
            'group'=>['Group Photo',       'grp'],
            '1'   => ['Single',           '1'],
            '2'   => ['Two (1×2)',         '2'],
            '3'   => ['Three (1×3)',       '3'],
            '2-2' => ['Four (2×2)',        '4'],
            '3-2' => ['Five (3+2)',        '5'],
            '3-3' => ['Nine (3×3)',        '9'],
          ];
          foreach ($layouts as $lv => [$ll, $lc]):
            $checked = ($layout_val === $lv) ? 'checked' : '';
          ?>
          <label class="layout-opt">
            <input type="radio" name="layout_type" value="<?= $lv ?>" <?= $checked ?> onchange="updateLayoutFields(this.value)">
            <div class="layout-opt-box">
              <div class="layout-opt-visual">
                <?php if ($lv==='0'): ?>
                  <span style="font-size:18px;opacity:.3">✕</span>
                <?php elseif ($lv==='group'): ?>
                  <div class="lv-dot" style="border-radius:4px;background:var(--text3)"></div>
                <?php else:
                  $n = (int)preg_replace('/\D/','',explode('-',$lv)[0]??$lv);
                  for($x=0;$x<min($n,3);$x++): ?><div class="lv-dot"></div><?php endfor;
                endif; ?>
              </div>
              <div class="layout-opt-label"><?= $ll ?></div>
            </div>
          </label>
          <?php endforeach; ?>
        </div>
        <!-- Dynamic employee/group image fields -->
        <div id="layout-fields"></div>
      </div>

      <!-- ④ Signature -->
      <div class="card">
        <div class="card-header">
          <div class="card-title" style="display:flex;align-items:center;gap:8px">
            <span style="font-size:16px">✒</span> Email Signature
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Regards Text</label>
          <input type="text" name="regards_text" class="form-control"
            value="<?= h($posted['regards_text'] ?? 'Best Regards,') ?>" placeholder="Best Regards,">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Your Name</label>
            <input type="text" name="regards_name" class="form-control"
              value="<?= h($posted['regards_name'] ?? '') ?>" placeholder="Full Name">
          </div>
          <div class="form-group">
            <label class="form-label">Title / Position</label>
            <input type="text" name="regards_title" class="form-control"
              value="<?= h($posted['regards_title'] ?? '') ?>" placeholder="e.g. Marketing Manager">
          </div>
        </div>
      </div>

      <!-- Submit row -->
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <button type="submit" class="btn btn-primary" id="et-submit" onclick="syncTinyMCE()">
          ⚡ Generate Template
        </button>
        <button type="reset" class="btn btn-ghost" onclick="if(!confirm('Clear all fields?'))return false;resetForm()">
          ↺ Reset
        </button>
        <span style="font-size:12px;color:var(--text3)">Fields marked * are required</span>
      </div>
    </form>
  </div>

  <!-- ══ RIGHT: PREVIEW ══ -->
  <div class="et-preview">
    <div class="et-preview-header">
      <div class="et-preview-title">📧 Preview</div>
      <?php if ($preview_html): ?>
      <div class="et-preview-actions">
        <button class="btn btn-ghost btn-sm" onclick="copyForClient('outlook')">
          <i class="fas fa-envelope"></i> Copy for Outlook
        </button>
        <button class="btn btn-primary btn-sm" onclick="copyForClient('gmail')">
          <i class="fab fa-google"></i> Copy for Gmail
        </button>
        <button class="btn btn-ghost btn-sm" onclick="downloadTemplate()" title="Download HTML">
          ↓ Download
        </button>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($preview_html): ?>
    <!-- Scaled preview iframe -->
    <div id="et-preview-frame">
      <iframe id="preview-iframe"
        style="width:750px;height:600px;border:none;transform-origin:top left;display:block"
        sandbox="allow-same-origin"
        title="Email preview"></iframe>
    </div>
    <!-- Copy instructions -->
    <div style="margin-top:12px;padding:10px 14px;background:var(--bg3);border-radius:var(--radius-sm);font-size:12px;color:var(--text3)">
      <strong style="color:var(--text2)">📋 How to use:</strong>
      Click <em>Copy for Outlook/Gmail</em>, open a new email, and paste (<kbd style="background:var(--bg4);padding:1px 5px;border-radius:3px;border:1px solid var(--border)">Ctrl+V</kbd>).
      Choose "Keep Source Formatting" if prompted.
    </div>
    <?php else: ?>
    <div class="et-preview-empty">
      <div class="et-preview-empty-icon">📨</div>
      <div style="font-size:14px;font-weight:700;color:var(--text2);margin-bottom:6px">Your email preview will appear here</div>
      <div style="font-size:12.5px">Fill in the form and click <strong>Generate Template</strong></div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Copy toast -->
<div class="copy-toast" id="copy-toast">✓ Copied to clipboard!</div>

<!-- Embed generated HTML for JS access -->
<?php if ($preview_html): ?>
<script id="et-html-data" type="application/x-email-template"><?= htmlspecialchars($preview_html, ENT_QUOTES, 'UTF-8') ?></script>
<?php endif; ?>

<script>
// ══ TINYMCE INIT ══
var isDark = document.documentElement.getAttribute('data-theme') !== 'light';

tinymce.init({
  selector: '#et-tinymce',
  height: 320,
  menubar: false,
  skin: isDark ? 'oxide-dark' : 'oxide',
  content_css: isDark ? 'dark' : 'default',
  plugins: ['lists','link','table','code','fontsize','image'],
  toolbar: 'undo redo | fontsize | bold italic underline | forecolor | alignleft aligncenter alignright | bullist numlist | table link | code',
  font_size_formats: '10pt 11pt 12pt 14pt 16pt 18pt 24pt',
  content_style: "body{font-family:'Proxima Nova',Arial,sans-serif;font-size:12pt;line-height:1.6;padding:8px}",
  promotion: false, branding: false,
  setup: function(ed) {
    document.addEventListener('themeChanged', function(e) {
      // Reload TinyMCE skin on theme change
      var content = ed.getContent();
      tinymce.remove('#et-tinymce');
      isDark = e.detail.theme === 'dark';
      tinymce.init(tinymce.settings);
    });
  }
});

function syncTinyMCE() {
  var ed = tinymce.get('et-tinymce');
  if (ed) document.querySelector('[name="body_content"]').value = ed.getContent();
  return true;
}

// ══ LAYOUT FIELD BUILDER ══
var MAX_EMP = {'1':1,'2':2,'3':3,'2-2':4,'3-2':5,'3-3':9};

function updateLayoutFields(type) {
  var c = document.getElementById('layout-fields');
  c.innerHTML = '';
  if (type === '0') return;

  if (type === 'group') {
    c.innerHTML = '<div class="emp-card"><div class="emp-card-title">Group Photo</div>'
      + buildDrop('group_image', 'drop-g', 'Group Photo')
      + '<div class="form-group" style="margin-top:10px">'
      + '<label class="form-label">Caption (optional)</label>'
      + '<input type="text" name="group_caption" class="form-control" placeholder="e.g. The Padak Team">'
      + '</div></div>';
    return;
  }

  var n = MAX_EMP[type] || 0;
  for (var i = 1; i <= n; i++) {
    var did = 'drop-emp-'+i;
    c.innerHTML += '<div class="emp-card">'
      + '<div class="emp-card-title">Employee ' + i + '</div>'
      + buildDrop('employee_image_'+i, did, 'Employee photo (optional)')
      + '<div class="form-row" style="margin-top:10px">'
      + '<div class="form-group"><label class="form-label">Name</label><input type="text" name="employee_name_'+i+'" class="form-control" placeholder="Full Name"></div>'
      + '<div class="form-group"><label class="form-label">Title</label><input type="text" name="employee_title_'+i+'" class="form-control" placeholder="Position"></div>'
      + '</div></div>';
  }
  // Re-attach change listeners
  document.querySelectorAll('#layout-fields input[type=file]').forEach(function(inp) {
    inp.addEventListener('change', function() { previewDrop(this, this.dataset.drop); });
  });
}

function buildDrop(name, dropId, label) {
  return '<div class="file-drop" id="'+dropId+'">'
    + '<input type="file" name="'+name+'" accept="image/*" data-drop="'+dropId+'" onchange="previewDrop(this,\''+dropId+'\')">'
    + '<div class="file-drop-icon">🖼</div>'
    + '<div class="file-drop-text">'+label+'</div>'
    + '<div class="file-drop-name" id="'+dropId+'-name"></div>'
    + '</div>';
}

function previewDrop(inp, dropId) {
  var nameEl = document.getElementById(dropId + '-name');
  if (!inp.files[0]) return;
  nameEl.textContent = '✓ ' + inp.files[0].name;
  var drop = document.getElementById(dropId);
  if (drop) drop.style.borderColor = 'var(--green)';
}

// Init layout fields on load if a layout was posted
document.addEventListener('DOMContentLoaded', function() {
  var checked = document.querySelector('input[name="layout_type"]:checked');
  if (checked) updateLayoutFields(checked.value);

  // Load preview iframe
  var data = document.getElementById('et-html-data');
  if (data) {
    var html = data.textContent;
    var iframe = document.getElementById('preview-iframe');
    if (iframe) {
      var doc = iframe.contentDocument || iframe.contentWindow.document;
      doc.open(); doc.write(html); doc.close();
      // Scale to fit preview panel
      scalePreview();
    }
  }

  // Drag-over styling
  document.querySelectorAll('.file-drop').forEach(function(d) {
    d.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('drag'); });
    d.addEventListener('dragleave', function() { this.classList.remove('drag'); });
    d.addEventListener('drop', function(e) {
      e.preventDefault(); this.classList.remove('drag');
      var inp = this.querySelector('input[type=file]');
      if (inp && e.dataTransfer.files[0]) {
        inp.files = e.dataTransfer.files;
        previewDrop(inp, this.id);
      }
    });
  });
});

function scalePreview() {
  var iframe = document.getElementById('preview-iframe');
  var wrap   = document.getElementById('et-preview-frame');
  if (!iframe || !wrap) return;
  var available = wrap.offsetWidth;
  var scale     = Math.min(1, available / 750);
  iframe.style.transform = 'scale(' + scale + ')';
  iframe.style.height    = '600px';
  wrap.style.height      = (600 * scale) + 'px';
}
window.addEventListener('resize', scalePreview);

// ══ COPY FUNCTIONS ══
function getEmailHTML() {
  var data = document.getElementById('et-html-data');
  return data ? data.textContent : '';
}

function showCopyToast(msg) {
  var t = document.getElementById('copy-toast');
  t.textContent = '✓ ' + msg;
  t.classList.add('show');
  setTimeout(function() { t.classList.remove('show'); }, 2500);
}

function copyForClient(client) {
  var html = getEmailHTML();
  if (!html) return;

  // Use Clipboard API with HTML mime type
  if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
    var blob = new Blob([html], {type: 'text/html'});
    var text = new Blob([stripTags(html)], {type: 'text/plain'});
    navigator.clipboard.write([new ClipboardItem({'text/html': blob, 'text/plain': text})])
      .then(function() { showCopyToast('Copied for ' + (client === 'outlook' ? 'Outlook' : 'Gmail') + '!'); })
      .catch(function() { fallbackCopy(html, client); });
  } else {
    fallbackCopy(html, client);
  }
}

function fallbackCopy(html, client) {
  // iframe selection method
  var iframe = document.createElement('iframe');
  iframe.style.cssText = 'position:fixed;left:-9999px;top:0;width:800px;height:500px';
  document.body.appendChild(iframe);
  var doc = iframe.contentDocument || iframe.contentWindow.document;
  doc.open(); doc.write(html); doc.close();
  var range = doc.createRange();
  range.selectNodeContents(doc.body);
  var sel = iframe.contentWindow.getSelection();
  sel.removeAllRanges(); sel.addRange(range);
  var ok = doc.execCommand('copy');
  document.body.removeChild(iframe);
  if (ok) showCopyToast('Copied! Paste into ' + client);
  else toast('Use Ctrl+A in the preview iframe, then Ctrl+C', 'info');
}

function stripTags(html) {
  var tmp = document.createElement('div');
  tmp.innerHTML = html;
  return tmp.textContent || tmp.innerText || '';
}

function downloadTemplate() {
  var html = getEmailHTML();
  if (!html) return;
  var blob = new Blob([html], {type: 'text/html'});
  var a    = document.createElement('a');
  a.href   = URL.createObjectURL(blob);
  a.download = 'padak-email-template-' + Date.now() + '.html';
  a.click();
  URL.revokeObjectURL(a.href);
  showCopyToast('Template downloaded!');
}

function resetForm() {
  if (tinymce.get('et-tinymce')) tinymce.get('et-tinymce').setContent('');
  document.getElementById('layout-fields').innerHTML = '';
  document.querySelector('input[name="layout_type"][value="0"]').checked = true;
}
</script>

<?php renderLayoutEnd(); ?>