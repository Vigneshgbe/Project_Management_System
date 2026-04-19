<?php
// email_template.php — Email Template Generator (Padak CRM)
// STANDALONE — no external includes required. Drop-in replacement.

ini_set('display_errors', 0);
error_reporting(E_ALL);

// ─── Upload directory ─────────────────────────────────────────────────────────
$upload_dir = "uploads/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
function etg_base64Image(string $path): string {
    if (!file_exists($path)) return '';
    $data = file_get_contents($path);
    if ($data === false) return '';
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $path);
    finfo_close($finfo);
    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

function etg_saveUpload(string $key, string $dir): string {
    if (!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) return '';
    if (!getimagesize($_FILES[$key]['tmp_name'])) return '';
    $ext     = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed, true)) return '';
    $dest = $dir . uniqid('etg_', true) . '.' . $ext;
    return move_uploaded_file($_FILES[$key]['tmp_name'], $dest) ? etg_base64Image($dest) : '';
}

// ─── Process POST ─────────────────────────────────────────────────────────────
$preview_html = '';
$form_error   = '';
$current_step = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body_content    = $_POST['body_content']    ?? '';
    $regards_text    = $_POST['regards_text']    ?? 'Best Regards,';
    $signature_name  = $_POST['regards_name']    ?? '';
    $signature_title = $_POST['regards_title']   ?? '';
    $layout_type     = $_POST['layout_type']     ?? '0';

    $header_image_data = etg_saveUpload('header_image', $upload_dir);

    if (empty(trim(strip_tags($body_content)))) {
        $form_error   = 'Please provide email content before generating.';
        $current_step = 1;
    } else {
        $current_step = 4;
        $options = [
            'header_image'    => $header_image_data,
            'body_content'    => $body_content,
            'signature_name'  => $signature_name,
            'signature_title' => $signature_title,
            'layout_type'     => $layout_type,
            'regards_text'    => $regards_text,
        ];

        if ($layout_type === 'group') {
            $options['group_image']   = etg_saveUpload('group_image', $upload_dir);
            $options['group_caption'] = $_POST['group_caption'] ?? '';
        } else {
            $maxMap = ['1'=>1,'2'=>2,'3'=>3,'2-2'=>4,'3-2'=>5,'3-3'=>9];
            $max    = $maxMap[$layout_type] ?? 0;
            $imgs   = [];
            $dets   = [];
            for ($i = 1; $i <= $max; $i++) {
                $imgs[] = etg_saveUpload("employee_image_{$i}", $upload_dir);
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

// ─── Template engine ──────────────────────────────────────────────────────────
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

    $font = '"Proxima Nova RG","Proxima Nova",Arial,sans-serif';
    $w    = 750;
    $body = etg_processContent($body_content);

    $tpl  = '<!DOCTYPE html>
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
<body style="margin:0;padding:0;background:#ffffff;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" width="' . $w . '" style="width:' . $w . 'px;max-width:100%;">';

    if ($header_image) {
        $tpl .= '<tr><td><img src="' . $header_image . '" width="' . $w . '" alt="Header" style="display:block;width:' . $w . 'px;max-width:100%;"/></td></tr>';
    }

    $tpl .= '<tr><td style="padding:30px 50px;font-family:' . $font . ';line-height:1.5;">' . $body . '</td></tr>';

    if ($layout_type === 'group' && !empty($group_image)) {
        $tpl .= etg_groupSection($group_image, $group_caption, $font, $w);
    } elseif (!empty($employee_images)) {
        $tpl .= etg_employeeGrid($employee_images, $employee_details, $font);
    }

    $tpl .= '<tr><td style="padding:10px 50px 24px;font-family:' . $font . ';">
<p style="margin:0 0 6px;color:#333;"><strong>' . htmlspecialchars($regards_text) . '</strong></p>
<p style="margin:0;color:#333;"><strong>' . htmlspecialchars($signature_name) . '</strong><br>
<span style="color:#666;">' . htmlspecialchars($signature_title) . '</span></p>
</td></tr>
<tr><td style="background:#000000;padding:15px;text-align:center;font-family:' . $font . ';font-size:13px;color:#ffffff;">
Padak (Pvt) Ltd, Batticaloa, Sri Lanka &mdash; +94 710815522
</td></tr>
</table></body></html>';

    return $tpl;
}

function etg_groupSection(string $img, string $cap, string $font, int $w): string {
    $iw = 600;
    $h  = '<tr><td align="center" style="padding:20px 0;"><div style="text-align:center;max-width:' . $iw . 'px;margin:0 auto;">';
    $h .= '<img src="' . $img . '" width="' . $iw . '" style="max-width:100%;display:block;border-radius:5px;" alt="Group"/>';
    if ($cap) {
        $h .= '<div style="padding-top:12px;font-style:italic;font-size:14px;font-family:' . $font . ';">' . htmlspecialchars($cap) . '</div>';
    }
    $h .= '</div></td></tr>';
    return $h;
}

function etg_employeeGrid(array $imgs, array $dets, string $font): string {
    $items = [];
    foreach ($imgs as $i => $img) {
        if (!empty($img)) {
            $items[] = ['img' => $img, 'det' => $dets[$i] ?? ['name'=>'','title'=>'']];
        }
    }
    if (!$items) return '';

    $count = count($items);
    $cols  = $count <= 2 ? $count : 3;

    $cellFn = function(array $item) use ($font): string {
        $c  = '<td align="center" style="padding:10px;vertical-align:top;">';
        $c .= '<img src="' . $item['img'] . '" width="200" height="200" style="display:block;border-radius:5px;object-fit:cover;" alt=""/>';
        if (!empty($item['det']['name'])) {
            $c .= '<div style="padding-top:10px;font-family:' . $font . ';">';
            $c .= '<strong style="font-size:15px;">' . htmlspecialchars($item['det']['name']) . '</strong><br>';
            $c .= '<span style="font-size:13px;color:#666;">' . htmlspecialchars($item['det']['title']) . '</span>';
            $c .= '</div>';
        }
        $c .= '</td>';
        return $c;
    };

    $html   = '<tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" align="center">';
    $chunks = array_chunk($items, $cols);
    foreach ($chunks as $row) {
        $html .= '<tr>';
        foreach ($row as $item) $html .= $cellFn($item);
        for ($p = 0, $pad = $cols - count($row); $p < $pad; $p++) {
            $html .= '<td style="width:220px;"></td>';
        }
        $html .= '</tr>';
    }
    $html .= '</table></td></tr>';
    return $html;
}

function etg_processContent(string $html): string {
    if (!$html) return '';
    $font = '"Proxima Nova RG","Proxima Nova",Arial,sans-serif';
    $dom  = new DOMDocument();
    libxml_use_internal_errors(true);
    @$dom->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    foreach ((new DOMXPath($dom))->query('//*') as $el) {
        $styles = [];
        foreach (explode(';', $el->getAttribute('style')) as $s) {
            $s = trim($s);
            if (!$s) continue;
            $parts = explode(':', $s, 2);
            if (count($parts) === 2) $styles[trim($parts[0])] = trim($parts[1]);
        }
        $styles['font-family'] = $font;
        $el->setAttribute('style', implode(';', array_map(
            fn($k, $v) => "$k:$v", array_keys($styles), $styles
        )));
    }
    $out = $dom->saveHTML();
    return preg_replace([
        '/<\?xml[^>]+>/i',
        '/<\/?html[^>]*>/i',
        '/<\/?head[^>]*>/i',
        '/<\/?body[^>]*>/i'
    ], '', $out);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Email Template Generator — Padak CRM</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<script>
/* Apply saved theme before CSS loads to prevent flash */
(function(){
    var t = localStorage.getItem('padak_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', t);
})();
</script>
<style>
/* ═══════════════════════════════════════════════════════
   CSS VARIABLES — mirrors layout.php exactly
═══════════════════════════════════════════════════════ */
:root, [data-theme="dark"] {
    --bg:        #0f1117;
    --bg2:       #161b27;
    --bg3:       #1e2538;
    --bg4:       #252d40;
    --border:    #2a3348;
    --border2:   #3a4560;
    --text:      #e8eaf0;
    --text2:     #9aa3b8;
    --text3:     #5a6478;
    --orange:    #f97316;
    --orange-bg: rgba(249,115,22,0.1);
    --green:     #10b981;
    --red:       #ef4444;
    --shadow:    0 1px 4px rgba(0,0,0,.4);
    --shadow-lg: 0 8px 30px rgba(0,0,0,.5);
    --radius:    10px;
    --radius-sm: 6px;
    --radius-lg: 16px;
    --font:      'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
[data-theme="light"] {
    --bg:        #f0f2f7;
    --bg2:       #ffffff;
    --bg3:       #f5f6fa;
    --bg4:       #eaecf2;
    --border:    #dde1ec;
    --border2:   #c8cdde;
    --text:      #111827;
    --text2:     #4b5563;
    --text3:     #9ca3af;
    --orange:    #f97316;
    --orange-bg: rgba(249,115,22,0.08);
    --green:     #059669;
    --red:       #dc2626;
    --shadow:    0 1px 4px rgba(0,0,0,.08);
    --shadow-lg: 0 8px 30px rgba(0,0,0,.12);
}

/* ═══════════════════════════════════════════════════════
   BASE
═══════════════════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { font-size: 14px; }
body {
    font-family: var(--font);
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    transition: background .2s, color .2s;
}
a { color: inherit; text-decoration: none; }
button, input, select, textarea { font-family: var(--font); }
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-track { background: var(--bg2); }
::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 99px; }

/* ═══════════════════════════════════════════════════════
   PAGE HEADER
═══════════════════════════════════════════════════════ */
.etg-header {
    background: var(--bg2);
    border-bottom: 1px solid var(--border);
    padding: 0 24px;
    height: 58px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 50;
    transition: background .2s, border-color .2s;
}
.etg-header-left { display: flex; align-items: center; gap: 12px; }
.etg-logo { width: 34px; height: 34px; background: var(--orange); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; color: #fff; flex-shrink: 0; }
.etg-page-title { font-size: 16px; font-weight: 700; color: var(--text); }
.etg-page-subtitle { font-size: 12px; color: var(--text3); font-weight: 400; margin-left: 6px; }
.etg-header-right { display: flex; align-items: center; gap: 10px; }
.etg-date { font-size: 12px; color: var(--text3); }

/* Theme toggle button */
.etg-theme-btn {
    width: 36px; height: 36px;
    border-radius: var(--radius-sm);
    background: var(--bg3);
    border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; cursor: pointer; color: var(--text2);
    transition: background .15s, border-color .15s, color .15s;
}
.etg-theme-btn:hover { background: var(--bg4); color: var(--text); border-color: var(--border2); }

/* ═══════════════════════════════════════════════════════
   LAYOUT
═══════════════════════════════════════════════════════ */
.etg-wrap { max-width: 1600px; margin: 0 auto; padding: 24px; }
.etg-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; }
@media(max-width:900px) { .etg-cols { grid-template-columns: 1fr; } }
.etg-left { overflow-y: auto; }

/* ═══════════════════════════════════════════════════════
   STEPPER
═══════════════════════════════════════════════════════ */
.etg-stepper { display: flex; margin-bottom: 24px; }
.etg-step { flex: 1; text-align: center; position: relative; }
.etg-step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 16px; left: 50%;
    width: 100%; height: 2px;
    background: var(--border);
    z-index: 0;
    transition: background .3s;
}
.etg-step.done:not(:last-child)::after,
.etg-step.active:not(:last-child)::after { background: var(--orange); }

.etg-step-circle {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: var(--bg4);
    border: 2px solid var(--border);
    color: var(--text3);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 6px;
    position: relative; z-index: 1;
    font-size: 12px; font-weight: 700;
    transition: background .3s, border-color .3s, color .3s, box-shadow .3s;
}
.etg-step.active .etg-step-circle {
    background: var(--orange);
    border-color: var(--orange);
    color: #fff;
    box-shadow: 0 0 0 4px var(--orange-bg);
}
.etg-step.done .etg-step-circle { background: var(--green); border-color: var(--green); color: #fff; }
.etg-step-label { font-size: 11px; color: var(--text3); font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
.etg-step.active .etg-step-label { color: var(--orange); }
.etg-step.done   .etg-step-label { color: var(--green); }

/* ═══════════════════════════════════════════════════════
   CARDS
═══════════════════════════════════════════════════════ */
.etg-card {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    margin-bottom: 14px;
    overflow: hidden;
    transition: background .2s, border-color .2s;
}
.etg-card-hd {
    display: flex; align-items: center; gap: 10px;
    padding: 13px 18px;
    border-bottom: 1px solid var(--border);
    background: var(--bg3);
    transition: background .2s, border-color .2s;
}
.etg-card-hd i { color: var(--orange); font-size: 15px; width: 18px; text-align: center; }
.etg-card-hd h3 { font-size: 14px; font-weight: 700; margin: 0; }
.etg-card-bd { padding: 18px; }

/* ═══════════════════════════════════════════════════════
   FORM CONTROLS
═══════════════════════════════════════════════════════ */
.form-group { margin-bottom: 14px; }
.form-group:last-child { margin-bottom: 0; }
.form-label { display: block; font-size: 11px; font-weight: 600; color: var(--text2); margin-bottom: 5px; text-transform: uppercase; letter-spacing: .04em; }
.form-control {
    width: 100%;
    background: var(--bg3);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 9px 12px;
    color: var(--text);
    font-size: 13.5px;
    transition: border-color .15s, background .2s, color .2s;
}
.form-control:focus { outline: none; border-color: var(--orange); }
.form-control::placeholder { color: var(--text3); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@media(max-width:600px) { .form-row { grid-template-columns: 1fr; } }

/* ═══════════════════════════════════════════════════════
   FILE DROP ZONE
═══════════════════════════════════════════════════════ */
.etg-drop {
    position: relative;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 6px;
    padding: 26px 16px;
    border: 2px dashed var(--border);
    border-radius: var(--radius);
    background: var(--bg3);
    cursor: pointer;
    transition: border-color .2s, background .2s;
    text-align: center;
}
.etg-drop:hover, .etg-drop.drag-over { border-color: var(--orange); background: var(--orange-bg); }
.etg-drop input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.etg-drop-icon { font-size: 24px; color: var(--orange); }
.etg-drop-title { font-size: 13px; font-weight: 600; color: var(--text); }
.etg-drop-hint  { font-size: 11px; color: var(--text3); }

.etg-file-pill {
    display: none;
    align-items: center;
    gap: 7px;
    margin-top: 10px;
    padding: 5px 14px;
    background: var(--bg4);
    border: 1px solid var(--border);
    border-radius: 99px;
    font-size: 12px;
    color: var(--text2);
    max-width: 100%;
    overflow: hidden;
}
.etg-file-pill.show { display: inline-flex; }
.etg-file-pill span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 240px; }

/* ═══════════════════════════════════════════════════════
   LAYOUT PICKER
═══════════════════════════════════════════════════════ */
.etg-layouts { display: grid; grid-template-columns: repeat(auto-fill, minmax(106px, 1fr)); gap: 10px; }
.etg-layout-opt { position: relative; }
.etg-layout-opt input[type=radio] { position: absolute; opacity: 0; width: 0; height: 0; }
.etg-layout-lbl {
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    padding: 12px 8px;
    border: 2px solid var(--border);
    border-radius: var(--radius);
    background: var(--bg3);
    cursor: pointer;
    transition: border-color .2s, background .2s;
}
.etg-layout-lbl:hover { border-color: var(--orange); background: var(--orange-bg); }
.etg-layout-opt input:checked + .etg-layout-lbl { border-color: var(--orange); background: var(--orange-bg); }
.etg-layout-vis {
    width: 64px; height: 44px;
    background: var(--bg4);
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    flex-wrap: wrap; gap: 2px; padding: 5px;
    transition: background .2s;
}
.etg-dot {
    background: var(--border2);
    border-radius: 2px;
    transition: background .2s;
}
.etg-layout-opt input:checked + .etg-layout-lbl .etg-dot { background: var(--orange); }
.etg-layout-name { font-size: 11px; font-weight: 600; color: var(--text2); text-align: center; }

/* ═══════════════════════════════════════════════════════
   BUTTONS
═══════════════════════════════════════════════════════ */
.btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 18px;
    border-radius: var(--radius-sm);
    font-size: 13px; font-weight: 600;
    border: none; cursor: pointer;
    transition: opacity .15s, transform .1s;
    font-family: var(--font);
}
.btn:active { transform: scale(.97); }
.btn-primary { background: var(--orange); color: #fff; }
.btn-primary:hover { opacity: .88; }
.btn-primary:disabled { opacity: .45; cursor: not-allowed; transform: none; }
.btn-ghost { background: var(--bg3); color: var(--text2); border: 1px solid var(--border); }
.btn-ghost:hover { background: var(--bg4); color: var(--text); }
.btn-success { background: var(--green); color: #fff; }
.btn-success:hover { opacity: .88; }
.btn-danger { background: rgba(239,68,68,.1); color: var(--red); border: 1px solid rgba(239,68,68,.25); }
.btn-danger:hover { background: rgba(239,68,68,.2); }
.btn-sm { padding: 6px 14px; font-size: 12px; }
.etg-actions { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 4px 0 0; }

/* ═══════════════════════════════════════════════════════
   ERROR BANNER
═══════════════════════════════════════════════════════ */
.etg-error {
    background: rgba(239,68,68,.1);
    border: 1px solid rgba(239,68,68,.3);
    border-radius: var(--radius-sm);
    padding: 12px 16px;
    color: var(--red);
    font-size: 13px;
    margin-bottom: 14px;
    display: flex; align-items: center; gap: 8px;
}

/* ═══════════════════════════════════════════════════════
   PREVIEW PANE
═══════════════════════════════════════════════════════ */
.etg-preview-wrap {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    position: sticky;
    top: 74px;
    transition: background .2s, border-color .2s;
}
.etg-preview-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 13px 18px;
    border-bottom: 1px solid var(--border);
    background: var(--bg3);
    gap: 10px; flex-wrap: wrap;
    transition: background .2s, border-color .2s;
}
.etg-preview-bar strong { font-size: 14px; font-weight: 700; }
.etg-preview-body { padding: 16px; max-height: calc(100vh - 190px); overflow-y: auto; }
.etg-preview-email { background: #fff; border-radius: var(--radius-sm); overflow: hidden; border: 1px solid var(--border); }
.etg-preview-empty {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 12px; padding: 56px 24px;
    border: 2px dashed var(--border);
    border-radius: var(--radius-lg);
    color: var(--text3); text-align: center;
}
.etg-preview-empty .big-icon { font-size: 44px; }

/* ═══════════════════════════════════════════════════════
   LOADING OVERLAY
═══════════════════════════════════════════════════════ */
#etg-loading {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,.65);
    z-index: 9000;
    flex-direction: column; align-items: center; justify-content: center; gap: 16px;
}
#etg-loading.show { display: flex; }
.etg-spinner {
    width: 44px; height: 44px;
    border: 4px solid rgba(255,255,255,.2);
    border-top-color: var(--orange);
    border-radius: 50%;
    animation: etgSpin .75s linear infinite;
}
#etg-loading p { color: #fff; font-size: 14px; font-weight: 600; }
@keyframes etgSpin { to { transform: rotate(360deg); } }

/* ═══════════════════════════════════════════════════════
   MODAL
═══════════════════════════════════════════════════════ */
.etg-modal-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,.6);
    z-index: 8000;
    align-items: center; justify-content: center; padding: 20px;
}
.etg-modal-overlay.show { display: flex; }
.etg-modal {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    width: 100%; max-width: 400px;
    padding: 24px;
    box-shadow: var(--shadow-lg);
    transition: background .2s;
}
.etg-modal h3 { font-size: 16px; font-weight: 700; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
.etg-modal p  { color: var(--text2); font-size: 13.5px; line-height: 1.5; margin-bottom: 22px; }
.etg-modal-footer { display: flex; justify-content: flex-end; gap: 10px; }

/* ═══════════════════════════════════════════════════════
   TOAST
═══════════════════════════════════════════════════════ */
#etg-toasts {
    position: fixed; bottom: 24px; right: 24px;
    z-index: 9999;
    display: flex; flex-direction: column; gap: 8px;
}
.etg-toast {
    background: var(--bg3);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 12px 16px;
    font-size: 13px; color: var(--text);
    box-shadow: var(--shadow-lg);
    display: flex; align-items: center; gap: 10px;
    min-width: 240px; max-width: 340px;
    animation: etgToastIn .2s ease;
}
.etg-toast.success { border-left: 3px solid var(--green); }
.etg-toast.error   { border-left: 3px solid var(--red); }
.etg-toast.info    { border-left: 3px solid var(--orange); }
@keyframes etgToastIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
</style>
</head>
<body>

<!-- ── Loading overlay ──────────────────────────────────────── -->
<div id="etg-loading">
  <div class="etg-spinner"></div>
  <p>Generating template…</p>
</div>

<!-- ── Reset confirm modal ──────────────────────────────────── -->
<div class="etg-modal-overlay" id="etg-reset-modal">
  <div class="etg-modal">
    <h3>⚠️ Reset Form?</h3>
    <p>All inputs, uploaded images, and the current preview will be cleared. This cannot be undone.</p>
    <div class="etg-modal-footer">
      <button class="btn btn-ghost" onclick="closeResetModal()">Cancel</button>
      <button class="btn btn-danger" onclick="doReset()"><i class="fas fa-redo"></i> Reset</button>
    </div>
  </div>
</div>

<!-- ── Page header ───────────────────────────────────────────── -->
<div class="etg-header">
  <div class="etg-header-left">
    <div class="etg-logo">P</div>
    <div>
      <span class="etg-page-title"><i class="fas fa-envelope-open-text" style="color:var(--orange);margin-right:6px;"></i>Email Template Generator</span>
      <span class="etg-page-subtitle">— Padak CRM</span>
    </div>
  </div>
  <div class="etg-header-right">
    <span class="etg-date" id="etg-date"></span>
    <button class="etg-theme-btn" id="etg-theme-btn" onclick="etgToggleTheme()" title="Toggle dark/light mode">
      <span id="etg-theme-icon">🌙</span>
    </button>
  </div>
</div>

<!-- ── Main content ──────────────────────────────────────────── -->
<div class="etg-wrap">
  <div class="etg-cols">

    <!-- ─── LEFT: Form ──────────────────────────────────────── -->
    <div class="etg-left">

      <!-- Stepper -->
      <div class="etg-stepper" id="etg-stepper">
        <?php
        $steps = ['Content','Images','Signature','Generate'];
        foreach ($steps as $si => $sl):
            $sn = $si + 1;
            $cls = '';
            if ($sn < $current_step) $cls = 'done';
            elseif ($sn === $current_step) $cls = 'active';
            $circle = $sn < $current_step ? '✓' : $sn;
        ?>
        <div class="etg-step <?= $cls ?>" data-step="<?= $sn ?>">
          <div class="etg-step-circle"><?= $circle ?></div>
          <div class="etg-step-label"><?= $sl ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php if ($form_error): ?>
      <div class="etg-error">
        <i class="fas fa-exclamation-triangle"></i>
        <?= htmlspecialchars($form_error) ?>
      </div>
      <?php endif; ?>

      <form id="etg-form" method="post" enctype="multipart/form-data">

        <!-- Header image -->
        <div class="etg-card">
          <div class="etg-card-hd"><i class="fas fa-image"></i><h3>Header Image</h3></div>
          <div class="etg-card-bd">
            <div class="etg-drop" id="drop-header">
              <i class="fas fa-cloud-upload-alt etg-drop-icon"></i>
              <div class="etg-drop-title">Drop header image or click to browse</div>
              <div class="etg-drop-hint">Recommended: 750px wide · JPG / PNG / GIF / WebP</div>
              <input type="file" name="header_image" accept="image/*"
                     onchange="showPill(this,'pill-header')">
            </div>
            <div class="etg-file-pill" id="pill-header">
              <i class="fas fa-file-image" style="color:var(--orange)"></i>
              <span></span>
            </div>
          </div>
        </div>

        <!-- Email body -->
        <div class="etg-card">
          <div class="etg-card-hd"><i class="fas fa-pen-fancy"></i><h3>Email Content</h3></div>
          <div class="etg-card-bd">
            <textarea id="rich-text-editor" name="body_content" style="display:block;width:100%;min-height:240px;"
            ><?= isset($_POST['body_content']) ? htmlspecialchars($_POST['body_content']) : '' ?></textarea>
            <p style="margin:8px 0 0;font-size:11px;color:var(--text3);">
              <i class="fas fa-info-circle"></i>
              Important dates and keywords are highlighted automatically in the output.
            </p>
          </div>
        </div>

        <!-- Layout picker -->
        <div class="etg-card">
          <div class="etg-card-hd"><i class="fas fa-th-large"></i><h3>Photo Layout</h3></div>
          <div class="etg-card-bd">
            <div class="etg-layouts">

              <!-- No photos -->
              <div class="etg-layout-opt">
                <input type="radio" name="layout_type" value="0" id="lt-0"
                  <?= (($_POST['layout_type'] ?? '0') === '0') ? 'checked' : '' ?>
                  onchange="buildImageFields('0')">
                <label for="lt-0" class="etg-layout-lbl">
                  <div class="etg-layout-vis">
                    <i class="fas fa-ban" style="color:var(--text3);font-size:16px;"></i>
                  </div>
                  <span class="etg-layout-name">No Photos</span>
                </label>
              </div>

              <!-- Group -->
              <div class="etg-layout-opt">
                <input type="radio" name="layout_type" value="group" id="lt-group"
                  <?= (($_POST['layout_type'] ?? '') === 'group') ? 'checked' : '' ?>
                  onchange="buildImageFields('group')">
                <label for="lt-group" class="etg-layout-lbl">
                  <div class="etg-layout-vis">
                    <i class="fas fa-users" style="color:var(--orange);font-size:16px;"></i>
                  </div>
                  <span class="etg-layout-name">Group</span>
                </label>
              </div>

              <?php
              /* [value, label, [dots as [width,height] percentages]] */
              $layouts = [
                ['1',   'Single', [['90%','90%']]],
                ['2',   '1 × 2',  [['46%','90%'],['46%','90%']]],
                ['3',   '1 × 3',  [['29%','90%'],['29%','90%'],['29%','90%']]],
                ['2-2', '2 × 2',  [['46%','42%'],['46%','42%'],['46%','42%'],['46%','42%']]],
                ['3-2', '3 + 2',  [['29%','42%'],['29%','42%'],['29%','42%'],['46%','42%'],['46%','42%']]],
                ['3-3', '3 × 3',  [['29%','28%'],['29%','28%'],['29%','28%'],['29%','28%'],['29%','28%'],['29%','28%'],['29%','28%'],['29%','28%'],['29%','28%']]],
              ];
              foreach ($layouts as [$val, $label, $dots]):
                $checked = (($_POST['layout_type'] ?? '') === $val) ? 'checked' : '';
              ?>
              <div class="etg-layout-opt">
                <input type="radio" name="layout_type" value="<?= $val ?>" id="lt-<?= $val ?>"
                  <?= $checked ?> onchange="buildImageFields('<?= $val ?>')">
                <label for="lt-<?= $val ?>" class="etg-layout-lbl">
                  <div class="etg-layout-vis">
                    <?php foreach ($dots as [$dw, $dh]): ?>
                    <div class="etg-dot" style="width:<?= $dw ?>;height:<?= $dh ?>;"></div>
                    <?php endforeach; ?>
                  </div>
                  <span class="etg-layout-name"><?= $label ?></span>
                </label>
              </div>
              <?php endforeach; ?>
            </div>

            <!-- Dynamic employee / group fields injected here -->
            <div id="etg-img-fields" style="margin-top:14px;display:flex;flex-direction:column;gap:12px;"></div>
          </div>
        </div>

        <!-- Signature -->
        <div class="etg-card">
          <div class="etg-card-hd"><i class="fas fa-signature"></i><h3>Signature</h3></div>
          <div class="etg-card-bd">
            <div class="form-group">
              <label class="form-label">Closing phrase</label>
              <input type="text" name="regards_text" class="form-control"
                     placeholder="e.g. Best Regards,"
                     value="<?= htmlspecialchars($_POST['regards_text'] ?? 'Best Regards,') ?>">
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="regards_name" class="form-control"
                       placeholder="Full name"
                       value="<?= htmlspecialchars($_POST['regards_name'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Title / Position</label>
                <input type="text" name="regards_title" class="form-control"
                       placeholder="e.g. Project Manager"
                       value="<?= htmlspecialchars($_POST['regards_title'] ?? '') ?>">
              </div>
            </div>
          </div>
        </div>

        <div class="etg-actions">
          <button type="button" class="btn btn-ghost" onclick="openResetModal()">
            <i class="fas fa-redo"></i> Reset
          </button>
          <button type="submit" class="btn btn-primary" id="etg-submit">
            <i class="fas fa-paper-plane"></i> Generate Template
          </button>
        </div>

      </form>
    </div><!-- /left -->

    <!-- ─── RIGHT: Preview ──────────────────────────────────── -->
    <div>
      <div class="etg-preview-wrap">
        <div class="etg-preview-bar">
          <strong>
            <i class="fas fa-eye" style="color:var(--orange);margin-right:6px;"></i>Preview
          </strong>
          <?php if ($preview_html): ?>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button class="btn btn-ghost btn-sm" onclick="copyEmail('outlook')">
              <i class="fas fa-copy"></i> Outlook
            </button>
            <button class="btn btn-success btn-sm" onclick="copyEmail('gmail')">
              <i class="fas fa-envelope"></i> Gmail
            </button>
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
            <strong style="color:var(--text2);font-size:15px;">Preview will appear here</strong>
            <span style="font-size:12px;">Fill in the form and click "Generate Template"</span>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div><!-- /right -->

  </div><!-- /etg-cols -->
</div><!-- /etg-wrap -->

<!-- Toast container -->
<div id="etg-toasts"></div>

<!-- TinyMCE -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.7.0/tinymce.min.js" referrerpolicy="origin"></script>
<script>
/* ═════════════════════════════════════════════════════════
   THEME
═════════════════════════════════════════════════════════ */
function etgGetTheme() {
    return document.documentElement.getAttribute('data-theme') || 'dark';
}
function etgApplyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('padak_theme', theme);
    var icon = document.getElementById('etg-theme-icon');
    if (icon) icon.textContent = theme === 'dark' ? '🌙' : '☀️';
}
function etgToggleTheme() {
    var next = etgGetTheme() === 'dark' ? 'light' : 'dark';
    etgApplyTheme(next);
    reInitTinyMCE();
    // Notify layout.php sidebar if it exists on same page
    document.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: next } }));
}
// Sync icon on load (theme already applied by inline script)
etgApplyTheme(etgGetTheme());

// Listen if layout.php sidebar fires theme change
document.addEventListener('themeChanged', function(e) {
    etgApplyTheme(e.detail.theme);
    reInitTinyMCE();
});

// Date in header
(function(){
    var d = new Date();
    var days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var el = document.getElementById('etg-date');
    if (el) el.textContent = days[d.getDay()] + ', ' + months[d.getMonth()] + ' ' + d.getDate();
})();

/* ═════════════════════════════════════════════════════════
   TINYMCE
═════════════════════════════════════════════════════════ */
function getTmceSkin() {
    return etgGetTheme() === 'light'
        ? { skin: 'oxide',      content_css: '' }
        : { skin: 'oxide-dark', content_css: 'dark' };
}

function initTinyMCE() {
    if (typeof tinymce === 'undefined') return;
    var cfg = getTmceSkin();
    var initObj = {
        selector: '#rich-text-editor',
        height: 280,
        menubar: false,
        plugins: 'code lists link table',
        toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | code',
        skin: cfg.skin,
        content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5; }',
        setup: function(ed) {
            // Sync every change to the hidden textarea so form POST captures it
            ed.on('input change', function() { ed.save(); });
        }
    };
    if (cfg.content_css) initObj.content_css = cfg.content_css;
    tinymce.init(initObj);
}

function reInitTinyMCE() {
    if (typeof tinymce === 'undefined') return;
    try { tinymce.remove('#rich-text-editor'); } catch(e) {}
    setTimeout(initTinyMCE, 60);
}

/* ═════════════════════════════════════════════════════════
   DOM READY
═════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function() {
    initTinyMCE();

    // Rebuild image fields if a layout was selected (page reload after POST)
    var checked = document.querySelector('input[name="layout_type"]:checked');
    if (checked && checked.value !== '0') {
        buildImageFields(checked.value);
    }

    // Drag-over visual feedback on drop zones
    document.addEventListener('dragover', function(e) {
        var z = e.target.closest && e.target.closest('.etg-drop');
        if (z) { e.preventDefault(); z.classList.add('drag-over'); }
    });
    document.addEventListener('dragleave', function(e) {
        var z = e.target.closest && e.target.closest('.etg-drop');
        if (z && !z.contains(e.relatedTarget)) z.classList.remove('drag-over');
    });
    document.addEventListener('drop', function() {
        document.querySelectorAll('.etg-drop').forEach(function(z) { z.classList.remove('drag-over'); });
    });

    // Close modals on overlay click
    document.getElementById('etg-reset-modal').addEventListener('click', function(e) {
        if (e.target === this) closeResetModal();
    });
});

/* ═════════════════════════════════════════════════════════
   FILE PILLS
═════════════════════════════════════════════════════════ */
function showPill(input, pillId) {
    var pill = document.getElementById(pillId);
    if (!pill) return;
    if (input.files && input.files.length > 0) {
        pill.querySelector('span').textContent = input.files[0].name;
        pill.classList.add('show');
    } else {
        pill.classList.remove('show');
        pill.querySelector('span').textContent = '';
    }
}

/* ═════════════════════════════════════════════════════════
   DYNAMIC IMAGE FIELDS
═════════════════════════════════════════════════════════ */
function makeCard(iconCls, title, bodyHtml) {
    return '<div class="etg-card" style="margin:0;">' +
           '<div class="etg-card-hd"><i class="' + iconCls + '"></i><h3>' + title + '</h3></div>' +
           '<div class="etg-card-bd">' + bodyHtml + '</div>' +
           '</div>';
}

function buildImageFields(layout) {
    var container = document.getElementById('etg-img-fields');
    container.innerHTML = '';
    if (layout === '0') return;

    if (layout === 'group') {
        container.innerHTML = makeCard('fas fa-users', 'Group Photo',
            '<div class="etg-drop">' +
            '<i class="fas fa-users etg-drop-icon"></i>' +
            '<div class="etg-drop-title">Upload group photo</div>' +
            '<div class="etg-drop-hint">Wide landscape image works best</div>' +
            '<input type="file" name="group_image" accept="image/*" onchange="showPill(this,\'pill-group\')">' +
            '</div>' +
            '<div class="etg-file-pill" id="pill-group"><i class="fas fa-file-image" style="color:var(--orange)"></i><span></span></div>' +
            '<div class="form-group" style="margin-top:12px;">' +
            '<label class="form-label">Caption (optional)</label>' +
            '<input type="text" name="group_caption" class="form-control" placeholder="e.g. The team at our 2024 retreat">' +
            '</div>'
        );
        return;
    }

    var countMap = {'1':1, '2':2, '3':3, '2-2':4, '3-2':5, '3-3':9};
    var count = countMap[layout] || 0;

    for (var i = 1; i <= count; i++) {
        (function(idx) {
            var html =
                '<div class="etg-drop">' +
                '<i class="fas fa-user-circle etg-drop-icon"></i>' +
                '<div class="etg-drop-title">Upload photo</div>' +
                '<div class="etg-drop-hint">Square crop · 200×200px recommended</div>' +
                '<input type="file" name="employee_image_' + idx + '" accept="image/*" onchange="showPill(this,\'emp-pill-' + idx + '\')">' +
                '</div>' +
                '<div class="etg-file-pill" id="emp-pill-' + idx + '"><i class="fas fa-file-image" style="color:var(--orange)"></i><span></span></div>' +
                '<div class="form-row" style="margin-top:12px;">' +
                '<div class="form-group"><label class="form-label">Name</label><input type="text" name="employee_name_' + idx + '" class="form-control" placeholder="Employee name"></div>' +
                '<div class="form-group"><label class="form-label">Title</label><input type="text" name="employee_title_' + idx + '" class="form-control" placeholder="Job title"></div>' +
                '</div>';
            var wrapper = document.createElement('div');
            wrapper.innerHTML = makeCard('fas fa-user', 'Employee ' + idx, html);
            container.appendChild(wrapper.firstElementChild);
        })(i);
    }

    setStep(2);
}

/* ═════════════════════════════════════════════════════════
   STEPPER (JS-driven)
═════════════════════════════════════════════════════════ */
function setStep(n) {
    document.querySelectorAll('.etg-step').forEach(function(el) {
        var s = parseInt(el.dataset.step, 10);
        el.className = 'etg-step' +
            (s === n ? ' active' : '') +
            (s < n  ? ' done'   : '');
        el.querySelector('.etg-step-circle').textContent = s < n ? '✓' : s;
    });
}

/* ═════════════════════════════════════════════════════════
   RESET  ← THE KEY FIX
═════════════════════════════════════════════════════════ */
function openResetModal()  { document.getElementById('etg-reset-modal').classList.add('show'); }
function closeResetModal() { document.getElementById('etg-reset-modal').classList.remove('show'); }

function doReset() {
    closeResetModal();

    // 1. Clear TinyMCE editor content
    if (typeof tinymce !== 'undefined') {
        var ed = tinymce.get('rich-text-editor');
        if (ed) { ed.setContent(''); ed.save(); }
    }

    // 2. Reset all native form fields (text, select, file inputs, radios, checkboxes)
    var form = document.getElementById('etg-form');
    form.reset();

    // 3. Hide all file pills
    document.querySelectorAll('.etg-file-pill').forEach(function(p) {
        p.classList.remove('show');
        var sp = p.querySelector('span');
        if (sp) sp.textContent = '';
    });

    // 4. Clear dynamic image / employee fields
    document.getElementById('etg-img-fields').innerHTML = '';

    // 5. Ensure "No Photos" is selected (form.reset() should do this, but be explicit)
    var r0 = document.getElementById('lt-0');
    if (r0) r0.checked = true;

    // 6. Reset stepper to step 1
    setStep(1);

    etgToast('Form reset successfully.', 'success');
}

/* ═════════════════════════════════════════════════════════
   FORM SUBMIT
═════════════════════════════════════════════════════════ */
document.getElementById('etg-form').addEventListener('submit', function(e) {
    // Flush TinyMCE into textarea before submit
    if (typeof tinymce !== 'undefined') {
        var ed = tinymce.get('rich-text-editor');
        if (ed) ed.save();
    }

    // Validate: check for actual text content
    var hasContent = false;
    if (typeof tinymce !== 'undefined') {
        var ed2 = tinymce.get('rich-text-editor');
        if (ed2) hasContent = ed2.getContent({ format: 'text' }).trim().length > 0;
    }
    if (!hasContent) {
        var ta = document.querySelector('textarea[name="body_content"]');
        if (ta) hasContent = ta.value.trim().length > 0;
    }

    if (!hasContent) {
        e.preventDefault();
        etgToast('Please add email content before generating.', 'error');
        return;
    }

    // Show loading overlay and disable submit button
    document.getElementById('etg-loading').classList.add('show');
    var btn = document.getElementById('etg-submit');
    if (btn) btn.disabled = true;
    setStep(4);
});

/* ═════════════════════════════════════════════════════════
   COPY EMAIL
═════════════════════════════════════════════════════════ */
async function copyEmail(target) {
    var el = document.getElementById('etg-preview-content');
    if (!el) { etgToast('Nothing to copy yet.', 'error'); return; }
    var label = target === 'gmail' ? 'Gmail' : 'Outlook';

    // Modern API (best rich-text support in modern browsers)
    if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
        try {
            await navigator.clipboard.write([new ClipboardItem({
                'text/html':  new Blob([el.innerHTML], { type: 'text/html' }),
                'text/plain': new Blob([el.innerText],  { type: 'text/plain' })
            })]);
            etgToast('Copied! Paste into ' + label + ' compose window.', 'success');
            return;
        } catch(err) { /* fall through */ }
    }

    // execCommand fallback
    try {
        var range = document.createRange();
        range.selectNodeContents(el);
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
        var ok = document.execCommand('copy');
        sel.removeAllRanges();
        if (ok) {
            etgToast('Copied for ' + label + '! Paste into compose window.', 'success');
        } else {
            etgToast('Auto-copy failed — select all & copy manually (Ctrl+A, Ctrl+C).', 'error');
        }
    } catch(err) {
        etgToast('Could not copy — please select and copy manually.', 'error');
    }
}

/* ═════════════════════════════════════════════════════════
   TOAST HELPER
═════════════════════════════════════════════════════════ */
function etgToast(msg, type) {
    type = type || 'info';
    var icons = { success: '✓', error: '✕', info: 'ℹ' };
    var t = document.createElement('div');
    t.className = 'etg-toast ' + type;
    t.innerHTML = '<span>' + (icons[type] || 'ℹ') + '</span><span>' + msg + '</span>';
    document.getElementById('etg-toasts').appendChild(t);
    setTimeout(function() {
        t.style.transition = 'opacity .3s';
        t.style.opacity = '0';
        setTimeout(function() { if (t.parentNode) t.parentNode.removeChild(t); }, 320);
    }, 3500);
}
</script>
</body>
</html>