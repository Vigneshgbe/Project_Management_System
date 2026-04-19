<?php
/**
 * export_doc.php — Padak CRM Rich Document Export
 * Exports rich_docs content as:
 *   ?id=N&format=pdf   → PDF (via HTML→PDF using browser engine via proper headers)
 *   ?id=N&format=docx  → DOCX (pure PHP ZipArchive, no dependencies)
 */
require_once 'config.php';
requireLogin();
$db = getCRMDB();

$id     = (int)($_GET['id'] ?? 0);
$format = strtolower(trim($_GET['format'] ?? 'pdf'));

if (!$id || !in_array($format, ['pdf','docx'])) {
    http_response_code(400); die('Invalid request.');
}

$rd = $db->query("
    SELECT r.*, u.name AS author
    FROM rich_docs r
    LEFT JOIN users u ON u.id=r.created_by
    WHERE r.id=$id
")->fetch_assoc();

if (!$rd) { http_response_code(404); die('Document not found.'); }

$title   = $rd['title'];
$content = $rd['content'] ?? '';
$author  = $rd['author'] ?? 'Padak CRM';
$date    = date('F j, Y', strtotime($rd['updated_at']));
$safe_fn = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $title);

// ═══════════════════════════════════════════════════════
// PDF EXPORT — Pure PHP PDF binary, actual file download
// No external libraries. Writes raw PDF 1.4 syntax.
// ═══════════════════════════════════════════════════════
if ($format === 'pdf') {
    $pdf_bytes = buildPdf($title, $content, $author, $date, $rd['category']);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="'.$safe_fn.'.pdf"');
    header('Content-Length: '.strlen($pdf_bytes));
    header('Cache-Control: no-store');
    echo $pdf_bytes;
    exit;
}

// ═══════════════════════════════════════════════════════
// DOCX EXPORT — Pure PHP, uses ZipArchive (always available)
// Builds a valid .docx file from scratch
// ═══════════════════════════════════════════════════════
if ($format === 'docx') {
    $docx = buildDocx($title, $content, $author, $date, $rd['category']);
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="'.rawurlencode($safe_fn).'.docx"');
    header('Content-Length: '.strlen($docx));
    header('Cache-Control: no-store, no-cache');
    echo $docx;
    exit;
}


// ═══════════════════════════════════════════════════════
// PDF BUILDER — Pure PHP, writes valid PDF 1.4 binary
// Handles: title, meta, paragraphs, headings, bold,
// italic, tables (basic), lists, line-wrap, page-break
// ═══════════════════════════════════════════════════════
function buildPdf(string $title, string $html, string $author, string $date, string $category): string {

    // ── Parse HTML into flat line array ──
    $lines = htmlToTextLines($html, $title, $author, $date, $category);

    // ── PDF constants ──
    $W       = 595.28;   // A4 width pt
    $H       = 841.89;   // A4 height pt
    $ML      = 56.69;    // left margin
    $MR      = 56.69;    // right margin
    $MT      = 56.69;    // top margin
    $MB      = 56.69;    // bottom margin
    $TW      = $W - $ML - $MR;  // text width
    $FS_BODY = 11;
    $FS_H1   = 20;
    $FS_H2   = 16;
    $FS_H3   = 13;
    $FS_META = 9;
    $LINE_H  = 16;       // body line height
    $FONT_N  = 'Helvetica';
    $FONT_B  = 'Helvetica-Bold';
    $FONT_I  = 'Helvetica-Oblique';
    $FONT_BI = 'Helvetica-BoldOblique';
    $FONT_C  = 'Courier';

    // ── PDF object collector ──
    $objs   = [];
    $xrefs  = [];
    $pages  = [];
    $objNum = 0;

    $addObj = function(string $stream) use (&$objs, &$objNum): int {
        $objNum++;
        $objs[$objNum] = $stream;
        return $objNum;
    };

    // Build page content streams
    $cur_page_cmds = '';
    $y = $H - $MT;

    $newPage = function() use (&$cur_page_cmds, &$pages, &$y, &$addObj, $H, $MT, $MB, $ML, $TW, $W, $FONT_N, $FS_META, $title) {
        // Footer
        $ft = pdfEsc('Padak CRM  —  '.pdfTruncate($title, 60));
        $pn = count($pages) + 1;
        $cur_page_cmds .= "BT /{$FONT_N} {$FS_META} Tf {$ML} 20 Td ({$ft}) Tj ET\n";
        $cur_page_cmds .= "BT /{$FONT_N} {$FS_META} Tf ".($W-$ML-20)." 20 Td ({$pn}) Tj ET\n";
        // Top line
        $cur_page_cmds .= "{$ML} ".($H-$MT+8)." m ".($W-$ML)." ".($H-$MT+8)." l S\n";
        $pages[] = $cur_page_cmds;
        $cur_page_cmds = '';
        $y = $H - $MT;
    };

    $checkY = function(float $need) use (&$y, &$newPage, $MB) {
        if ($y - $need < $MB) $newPage();
    };

    $drawText = function(string $font, float $size, float $x, float $yy, string $text, array $rgb=[0,0,0]) use (&$cur_page_cmds) {
        $r = $rgb[0]; $g = $rgb[1]; $b = $rgb[2];
        $cur_page_cmds .= "{$r} {$g} {$b} rg\n";
        $cur_page_cmds .= "BT /{$font} {$size} Tf {$x} {$yy} Td (".pdfEsc($text).") Tj ET\n";
        $cur_page_cmds .= "0 0 0 rg\n";
    };

    $drawLine = function(float $x1, float $y1, float $x2, float $y2, float $w=0.5, array $rgb=[0.8,0.8,0.8]) use (&$cur_page_cmds) {
        $cur_page_cmds .= "{$rgb[0]} {$rgb[1]} {$rgb[2]} RG {$w} w {$x1} {$y1} m {$x2} {$y2} l S 0 0 0 RG\n";
    };

    // ── Approximate char width (Helvetica proportional) ──
    $charW = function(string $text, float $size): float {
        // Average width ratio for Helvetica ≈ 0.52
        return mb_strlen($text) * $size * 0.52;
    };

    // ── Word-wrap a line ──
    $wrapText = function(string $text, float $size, float $maxW) use ($charW): array {
        $words = explode(' ', $text);
        $lines = []; $cur = '';
        foreach ($words as $w) {
            $test = $cur ? $cur.' '.$w : $w;
            if ($charW($test, $size) <= $maxW) {
                $cur = $test;
            } else {
                if ($cur !== '') $lines[] = $cur;
                // If single word too long, truncate
                while ($charW($w, $size) > $maxW && mb_strlen($w) > 1) {
                    $w = mb_substr($w, 0, -1);
                }
                $cur = $w;
            }
        }
        if ($cur !== '') $lines[] = $cur;
        return $lines ?: [''];
    };

    // ── Draw title block on first page ──
    // Orange header bar
    $cur_page_cmds .= "0.976 0.451 0.086 rg\n{$ML} ".($y-2)." m ".($W-$ML)." ".($y-2)." l ".($W-$ML)." ".($y-26)." l {$ML} ".($y-26)." l f\n0 0 0 rg\n";
    // Title text (white)
    $cur_page_cmds .= "1 1 1 rg BT /{$FONT_B} {$FS_H1} Tf ".($ML+4)." ".($y-21)." Td (".pdfEsc(pdfTruncate($title,70)).") Tj ET 0 0 0 rg\n";
    $y -= 36;
    // Meta line
    $meta = $author.'  ·  '.$date.'  ·  '.$category;
    $cur_page_cmds .= "0.4 0.4 0.4 rg BT /{$FONT_I} {$FS_META} Tf {$ML} {$y} Td (".pdfEsc($meta).") Tj ET 0 0 0 rg\n";
    $y -= 6;
    // Divider
    $cur_page_cmds .= "0.976 0.451 0.086 RG 1 w {$ML} {$y} m ".($W-$ML)." {$y} l S 0 0 0 RG 0.5 w\n";
    $y -= 18;

    // ── Render lines ──
    foreach ($lines as $ln) {
        $type = $ln['type'];
        $text = $ln['text'] ?? '';

        switch ($type) {
            case 'h1':
                $checkY(36);
                $y -= 6;
                $drawLine($ML, $y+2, $W-$MR, $y+2, 0.5, [0.9,0.9,0.9]);
                $wrapped = $wrapText($text, $FS_H1, $TW);
                foreach ($wrapped as $wl) {
                    $checkY(28);
                    $drawText($FONT_B, $FS_H1, $ML, $y, $wl, [0.1,0.1,0.1]);
                    $y -= 26;
                }
                $drawLine($ML, $y+4, $W-$MR, $y+4, 0.4, [0.9,0.9,0.9]);
                $y -= 6;
                break;
            case 'h2':
                $checkY(28);
                $y -= 4;
                $wrapped = $wrapText($text, $FS_H2, $TW);
                foreach ($wrapped as $wl) {
                    $checkY(22);
                    $drawText($FONT_B, $FS_H2, $ML, $y, $wl, [0.15,0.15,0.15]);
                    $y -= 22;
                }
                $y -= 2;
                break;
            case 'h3':
                $checkY(20);
                $y -= 4;
                $wrapped = $wrapText($text, $FS_H3, $TW);
                foreach ($wrapped as $wl) {
                    $checkY(18);
                    $drawText($FONT_B, $FS_H3, $ML, $y, $wl, [0.22,0.22,0.22]);
                    $y -= 18;
                }
                break;
            case 'h4':
                $checkY(18);
                $y -= 2;
                $drawText($FONT_B, $FS_BODY+1, $ML, $y, pdfTruncate($text, 90), [0.3,0.3,0.3]);
                $y -= 16;
                break;
            case 'p':
                if (trim($text) === '') { $y -= 6; break; }
                $wrapped = $wrapText($text, $FS_BODY, $TW);
                foreach ($wrapped as $wl) {
                    $checkY($LINE_H);
                    $drawText($FONT_N, $FS_BODY, $ML, $y, $wl);
                    $y -= $LINE_H;
                }
                $y -= 3;
                break;
            case 'li':
                $bullet = ($ln['ordered'] ?? false) ? (($ln['index'] ?? 1).'.') : '•';
                $indent = $ML + 14;
                $maxW   = $TW - 14;
                $wrapped = $wrapText($text, $FS_BODY, $maxW);
                $first = true;
                foreach ($wrapped as $wl) {
                    $checkY($LINE_H);
                    if ($first) { $drawText($FONT_N, $FS_BODY, $ML+2, $y, $bullet); $first = false; }
                    $drawText($FONT_N, $FS_BODY, $indent, $y, $wl);
                    $y -= $LINE_H;
                }
                $y -= 2;
                break;
            case 'table':
                $rows   = $ln['rows'] ?? [];
                $cols   = max(array_map('count', $rows)) ?: 1;
                $colW   = $TW / $cols;
                $rowH   = 20;
                $checkY($rowH * min(count($rows), 3));
                foreach ($rows as $ri => $cells) {
                    if ($y - $rowH < $MB) { $newPage(); }
                    $isHead = ($ri === 0 && ($ln['has_head'] ?? false));
                    if ($isHead) {
                        $cur_page_cmds .= "0.95 0.95 0.95 rg {$ML} ".($y-$rowH+4)." m ".($W-$MR)." ".($y-$rowH+4)." l ".($W-$MR)." ".($y+4)." l {$ML} ".($y+4)." l f 0 0 0 rg\n";
                    }
                    // row border
                    $cur_page_cmds .= "0.85 0.85 0.85 RG 0.4 w {$ML} ".($y-$rowH+4)." m ".($W-$MR)." ".($y-$rowH+4)." l S 0 0 0 RG\n";
                    foreach ($cells as $ci => $cell) {
                        $cx  = $ML + $ci * $colW + 4;
                        $cty = $y - 2;
                        $ft  = $isHead ? $FONT_B : $FONT_N;
                        $txt = pdfTruncate(trim(strip_tags($cell ?? '')), (int)floor($colW / ($FS_BODY*0.52)));
                        $drawText($ft, $FS_BODY-1, $cx, $cty, $txt);
                        // vertical line
                        if ($ci > 0) {
                            $lx = $ML + $ci * $colW;
                            $cur_page_cmds .= "0.85 0.85 0.85 RG 0.3 w {$lx} ".($y+4)." m {$lx} ".($y-$rowH+4)." l S 0 0 0 RG\n";
                        }
                    }
                    $y -= $rowH;
                }
                // outer border
                $tableH = count($rows) * $rowH;
                $cur_page_cmds .= "0.7 0.7 0.7 RG 0.5 w {$ML} {$y} m ".($W-$MR)." {$y} l ".($W-$MR)." ".($y+$tableH)." l {$ML} ".($y+$tableH)." l S 0 0 0 RG\n";
                $y -= 8;
                break;
            case 'quote':
                $wrapped = $wrapText($text, $FS_BODY, $TW - 20);
                $qH = count($wrapped) * $LINE_H + 8;
                $checkY($qH);
                // Orange left bar
                $cur_page_cmds .= "0.976 0.451 0.086 rg ".($ML)." ".($y-$qH+12)." m ".($ML)." ".($y+4)." l ".($ML+3)." ".($y+4)." l ".($ML+3)." ".($y-$qH+12)." l f 0 0 0 rg\n";
                // Tinted bg
                $cur_page_cmds .= "1.0 0.97 0.93 rg ".($ML+3)." ".($y-$qH+12)." m ".($W-$MR)." ".($y-$qH+12)." l ".($W-$MR)." ".($y+4)." l ".($ML+3)." ".($y+4)." l f 0 0 0 rg\n";
                foreach ($wrapped as $wl) {
                    $drawText($FONT_I, $FS_BODY, $ML+14, $y, $wl, [0.22,0.22,0.22]);
                    $y -= $LINE_H;
                }
                $y -= 6;
                break;
            case 'code':
                $clines = explode("\n", $text);
                $checkY(min(count($clines), 4) * 14 + 10);
                $cH = count($clines) * 14 + 8;
                $cur_page_cmds .= "0.12 0.16 0.24 rg {$ML} ".($y-$cH+8)." m ".($W-$MR)." ".($y-$cH+8)." l ".($W-$MR)." ".($y+6)." l {$ML} ".($y+6)." l f 0 0 0 rg\n";
                foreach ($clines as $cl) {
                    if ($y - 14 < $MB) $newPage();
                    $txt = pdfTruncate($cl, 90);
                    $cur_page_cmds .= "0.89 0.91 0.94 rg BT /{$FONT_C} 9 Tf ".($ML+6)." {$y} Td (".pdfEsc($txt).") Tj ET 0 0 0 rg\n";
                    $y -= 13;
                }
                $y -= 8;
                break;
            case 'hr':
                $y -= 4;
                $drawLine($ML, $y, $W-$MR, $y, 0.5, [0.88,0.88,0.88]);
                $y -= 8;
                break;
            case 'pagebreak':
                $newPage();
                break;
        }
    }

    // Flush last page
    $newPage();

    // ── Assemble PDF ──
    $pdf  = "%PDF-1.4\n";
    $pdf .= "%\xe2\xe3\xcf\xd3\n"; // binary comment

    // Catalog + Pages placeholder (obj 1 + 2)
    $catalog_id = $addObj("<< /Type /Catalog /Pages 2 0 R >>");
    $pages_id   = 2; $objNum = max($objNum, 2);
    $objs[2]    = ''; // placeholder

    // Font resources (obj 3–8)
    $fonts = [];
    foreach (['Helvetica','Helvetica-Bold','Helvetica-Oblique','Helvetica-BoldOblique','Courier'] as $fn) {
        $fid = $addObj("<< /Type /Font /Subtype /Type1 /BaseFont /{$fn} /Encoding /WinAnsiEncoding >>");
        $fonts[$fn] = $fid;
    }

    $font_res = "/F1 {$fonts['Helvetica']} 0 R /F2 {$fonts['Helvetica-Bold']} 0 R /F3 {$fonts['Helvetica-Oblique']} 0 R /F4 {$fonts['Helvetica-BoldOblique']} 0 R /F5 {$fonts['Courier']} 0 R";

    // Map font names used in commands to /Fn references
    $font_map = [
        'Helvetica'            => 'F1',
        'Helvetica-Bold'       => 'F2',
        'Helvetica-Oblique'    => 'F3',
        'Helvetica-BoldOblique'=> 'F4',
        'Courier'              => 'F5',
    ];

    $page_ids = [];
    foreach ($pages as $pg) {
        // Replace font names with /Fn refs
        foreach ($font_map as $name => $ref) {
            $pg = str_replace('/'.$name.' ', '/'.$ref.' ', $pg);
        }
        $len = strlen($pg);
        $sid = $addObj("<< /Length {$len} >>\nstream\n{$pg}endstream");
        $pid = $addObj("<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Contents {$sid} 0 R /Resources << /Font << {$font_res} >> >> >>");
        $page_ids[] = $pid;
    }

    // Pages object
    $kids = implode(' 0 R ', $page_ids).' 0 R';
    $objs[2] = "<< /Type /Pages /Kids [{$kids}] /Count ".count($page_ids)." >>";

    // Info object
    $info_id = $addObj("<< /Title (".pdfEsc($title).") /Author (".pdfEsc($author).") /Producer (Padak CRM) /CreationDate (D:".date('YmdHis').") >>");

    // Build body + xref
    $offsets = [];
    foreach ($objs as $n => $body) {
        $offsets[$n] = strlen($pdf);
        $pdf .= "{$n} 0 obj\n{$body}\nendobj\n";
    }

    $xref_offset = strlen($pdf);
    $total = $objNum + 1;
    $pdf .= "xref\n0 {$total}\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= $objNum; $i++) {
        $pdf .= str_pad($offsets[$i] ?? 0, 10, '0', STR_PAD_LEFT)." 00000 n \n";
    }
    $pdf .= "trailer\n<< /Size {$total} /Root 1 0 R /Info {$info_id} 0 R >>\n";
    $pdf .= "startxref\n{$xref_offset}\n%%EOF\n";

    return $pdf;
}

function pdfEsc(string $s): string {
    // Convert to ISO-8859-1 (PDF standard encoding), escape PDF special chars
    $s = mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
    return str_replace(['\\','(',')',"\r","\n"], ['\\\\','\\(','\\)','',''], $s);
}

function pdfTruncate(string $s, int $max): string {
    return mb_strlen($s) > $max ? mb_substr($s, 0, $max-1).'…' : $s;
}

// ── HTML → structured line array for PDF renderer ──
function htmlToTextLines(string $html, string $title, string $author, string $date, string $category): array {
    if (!$html) return [['type'=>'p','text'=>'No content.']];

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $lines = [];
    $list_counters = [];

    function walkNodes(DOMNode $node, array &$lines, array &$list_counters, string $context = ''): void {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
            if ($text && $context === '') $lines[] = ['type'=>'p','text'=>$text];
            return;
        }
        if ($node->nodeType !== XML_ELEMENT_NODE) return;
        $tag = strtolower($node->nodeName);

        switch ($tag) {
            case 'h1': $lines[] = ['type'=>'h1','text'=>trim($node->textContent)]; break;
            case 'h2': $lines[] = ['type'=>'h2','text'=>trim($node->textContent)]; break;
            case 'h3': $lines[] = ['type'=>'h3','text'=>trim($node->textContent)]; break;
            case 'h4': case 'h5': case 'h6': $lines[] = ['type'=>'h4','text'=>trim($node->textContent)]; break;
            case 'p':
                $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
                if ($text) $lines[] = ['type'=>'p','text'=>$text];
                break;
            case 'br': $lines[] = ['type'=>'p','text'=>'']; break;
            case 'hr': $lines[] = ['type'=>'hr']; break;
            case 'ul':
                foreach ($node->childNodes as $li) {
                    if (strtolower($li->nodeName) === 'li') {
                        $lines[] = ['type'=>'li','text'=>trim(preg_replace('/\s+/',' ',$li->textContent)),'ordered'=>false];
                    }
                }
                break;
            case 'ol':
                $idx = 1;
                foreach ($node->childNodes as $li) {
                    if (strtolower($li->nodeName) === 'li') {
                        $lines[] = ['type'=>'li','text'=>trim(preg_replace('/\s+/',' ',$li->textContent)),'ordered'=>true,'index'=>$idx++];
                    }
                }
                break;
            case 'table':
                $rows = []; $has_head = false;
                foreach ($node->childNodes as $ch) {
                    $cn = strtolower($ch->nodeName);
                    if ($cn === 'thead') { $has_head = true; foreach ($ch->childNodes as $tr) { if (strtolower($tr->nodeName)==='tr') { $row=[]; foreach ($tr->childNodes as $td) { if (in_array(strtolower($td->nodeName),['td','th'])) $row[]=trim($td->textContent); } if ($row) $rows[]=$row; } } }
                    elseif (in_array($cn,['tbody','tfoot'])) { foreach ($ch->childNodes as $tr) { if (strtolower($tr->nodeName)==='tr') { $row=[]; foreach ($tr->childNodes as $td) { if (in_array(strtolower($td->nodeName),['td','th'])) $row[]=trim($td->textContent); } if ($row) $rows[]=$row; } } }
                    elseif ($cn==='tr') { $row=[]; foreach ($ch->childNodes as $td) { if (in_array(strtolower($td->nodeName),['td','th'])) $row[]=trim($td->textContent); } if ($row) $rows[]=$row; }
                }
                if ($rows) $lines[] = ['type'=>'table','rows'=>$rows,'has_head'=>$has_head];
                break;
            case 'blockquote':
                $text = trim(preg_replace('/\s+/',' ',$node->textContent));
                if ($text) $lines[] = ['type'=>'quote','text'=>$text];
                break;
            case 'pre':
                $lines[] = ['type'=>'code','text'=>$node->textContent];
                break;
            default:
                foreach ($node->childNodes as $child) walkNodes($child, $lines, $list_counters);
                break;
        }
    }

    $root = $dom->getElementsByTagName('div')->item(0);
    if ($root) {
        foreach ($root->childNodes as $child) {
            walkNodes($child, $lines, $list_counters);
        }
    }

    return $lines ?: [['type'=>'p','text'=>strip_tags($html)]];
}

// ═══════════════════════════════════════════════════════
// DOCX BUILDER — Pure PHP, no external libraries
// Produces valid Office Open XML (.docx)
// ═══════════════════════════════════════════════════════
function buildDocx(string $title, string $content, string $author, string $date, string $category): string {

    // Convert HTML content to OOXML paragraphs
    $body_xml = htmlToOoxml($content, $title, $author, $date, $category);

    // ── [Content_Types].xml ──
    $content_types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
  <Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
</Types>';

    // ── _rels/.rels ──
    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
</Relationships>';

    // ── word/_rels/document.xml.rels ──
    $word_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/>
</Relationships>';

    // ── word/styles.xml ──
    $styles = buildDocxStyles();

    // ── word/settings.xml ──
    $settings = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:defaultTabStop w:val="720"/>
  <w:compat><w:compatSetting w:name="compatibilityMode" w:uri="http://schemas.microsoft.com/office/word" w:val="15"/></w:compat>
</w:settings>';

    // ── docProps/core.xml ──
    $xt = xmlEsc($title); $xa = xmlEsc($author);
    $now = date('Y-m-d\TH:i:s\Z');
    $core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
  xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>'.$xt.'</dc:title>
  <dc:creator>'.$xa.'</dc:creator>
  <dcterms:created xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:modified>
</cp:coreProperties>';

    // ── word/document.xml ──
    $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas"
  xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
  xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"
  xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<w:body>
'.$body_xml.'
<w:sectPr>
  <w:pgSz w:w="12240" w:h="15840"/>
  <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440"/>
</w:sectPr>
</w:body>
</w:document>';

    // ── Build ZIP ──
    $tmp = sys_get_temp_dir().'/padak_docx_'.uniqid().'.docx';
    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml',         $content_types);
    $zip->addFromString('_rels/.rels',                  $rels);
    $zip->addFromString('word/document.xml',            $document);
    $zip->addFromString('word/styles.xml',              $styles);
    $zip->addFromString('word/settings.xml',            $settings);
    $zip->addFromString('word/_rels/document.xml.rels', $word_rels);
    $zip->addFromString('docProps/core.xml',            $core);
    $zip->close();

    $bytes = file_get_contents($tmp);
    @unlink($tmp);
    return $bytes;
}

function xmlEsc(string $s): string {
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

// ── HTML → OOXML converter ──
function htmlToOoxml(string $html, string $title, string $author, string $date, string $category): string {
    $NS = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"';
    $out = '';

    // Title paragraph
    $out .= '<w:p><w:pPr><w:pStyle w:val="Title"/></w:pPr><w:r><w:t>'.xmlEsc($title).'</w:t></w:r></w:p>';

    // Subtitle / meta
    $meta = $author.' · '.$date.' · '.$category;
    $out .= '<w:p><w:pPr><w:pStyle w:val="Subtitle"/></w:pPr><w:r><w:t>'.xmlEsc($meta).'</w:t></w:r></w:p>';

    // Separator
    $out .= '<w:p><w:pPr><w:pBdr><w:bottom w:val="single" w:sz="6" w:space="1" w:color="F97316"/></w:pBdr></w:pPr></w:p>';

    if (!$html) {
        $out .= '<w:p><w:r><w:t>No content.</w:t></w:r></w:p>';
        return $out;
    }

    // Parse DOM
    $dom = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8"><div id="__root__">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $root = $dom->getElementById('__root__');
    if (!$root) { $out .= '<w:p><w:r><w:t>'.xmlEsc(strip_tags($html)).'</w:t></w:r></w:p>'; return $out; }

    foreach ($root->childNodes as $node) {
        $out .= nodeToOoxml($node);
    }

    return $out;
}

function nodeToOoxml(DOMNode $node): string {
    if ($node->nodeType === XML_TEXT_NODE) {
        $text = trim($node->textContent);
        if ($text === '') return '';
        return '<w:p><w:r><w:t xml:space="preserve">'.xmlEsc($text).'</w:t></w:r></w:p>';
    }
    if ($node->nodeType !== XML_ELEMENT_NODE) return '';

    $tag = strtolower($node->nodeName);
    $out = '';

    switch ($tag) {
        case 'h1': case 'h2': case 'h3': case 'h4': case 'h5': case 'h6':
            $lvl_map = ['h1'=>'Heading1','h2'=>'Heading2','h3'=>'Heading3','h4'=>'Heading4','h5'=>'Heading4','h6'=>'Heading4'];
            $style = $lvl_map[$tag];
            $out .= '<w:p><w:pPr><w:pStyle w:val="'.$style.'"/></w:pPr>';
            $out .= inlineToOoxml($node);
            $out .= '</w:p>';
            break;

        case 'p':
            $out .= '<w:p><w:pPr><w:spacing w:after="120"/></w:pPr>';
            $out .= inlineToOoxml($node);
            $out .= '</w:p>';
            break;

        case 'ul': case 'ol':
            $isOrdered = ($tag === 'ol');
            foreach ($node->childNodes as $li) {
                if (strtolower($li->nodeName) === 'li') {
                    $out .= '<w:p><w:pPr>';
                    $out .= '<w:pStyle w:val="ListParagraph"/>';
                    $out .= '<w:numPr><w:ilvl w:val="0"/><w:numId w:val="'.($isOrdered?'2':'1').'"/></w:numPr>';
                    $out .= '</w:pPr>';
                    $out .= inlineToOoxml($li);
                    $out .= '</w:p>';
                }
            }
            break;

        case 'table':
            $out .= tableToOoxml($node);
            break;

        case 'blockquote':
            $out .= '<w:p><w:pPr><w:pStyle w:val="Quote"/><w:ind w:left="720"/></w:pPr>';
            $out .= inlineToOoxml($node);
            $out .= '</w:p>';
            break;

        case 'pre':
            $text = $node->textContent;
            // Split by newlines for readable code block
            foreach (explode("\n", $text) as $line) {
                $out .= '<w:p><w:pPr><w:pStyle w:val="CodeBlock"/></w:pPr>';
                $out .= '<w:r><w:rPr><w:rFonts w:ascii="Courier New" w:hAnsi="Courier New"/><w:sz w:val="18"/><w:color w:val="DC2626"/></w:rPr>';
                $out .= '<w:t xml:space="preserve">'.xmlEsc($line).'</w:t></w:r></w:p>';
            }
            break;

        case 'hr':
            $out .= '<w:p><w:pPr><w:pBdr><w:bottom w:val="single" w:sz="6" w:space="1" w:color="E5E7EB"/></w:pBdr></w:pPr></w:p>';
            break;

        case 'br':
            $out .= '<w:p></w:p>';
            break;

        case 'div': case 'section': case 'article': case 'main': case 'span':
            foreach ($node->childNodes as $child) {
                $out .= nodeToOoxml($child);
            }
            break;

        default:
            // Fallback: treat as paragraph
            $text = trim($node->textContent);
            if ($text) {
                $out .= '<w:p><w:r><w:t xml:space="preserve">'.xmlEsc($text).'</w:t></w:r></w:p>';
            }
            break;
    }
    return $out;
}

function inlineToOoxml(DOMNode $parent): string {
    $out = '';
    foreach ($parent->childNodes as $node) {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = $node->textContent;
            if ($text === '') continue;
            $out .= '<w:r><w:t xml:space="preserve">'.xmlEsc($text).'</w:t></w:r>';
        } elseif ($node->nodeType === XML_ELEMENT_NODE) {
            $tag = strtolower($node->nodeName);
            switch ($tag) {
                case 'strong': case 'b':
                    $out .= '<w:r><w:rPr><w:b/></w:rPr><w:t xml:space="preserve">'.xmlEsc($node->textContent).'</w:t></w:r>';
                    break;
                case 'em': case 'i':
                    $out .= '<w:r><w:rPr><w:i/></w:rPr><w:t xml:space="preserve">'.xmlEsc($node->textContent).'</w:t></w:r>';
                    break;
                case 'u':
                    $out .= '<w:r><w:rPr><w:u w:val="single"/></w:rPr><w:t xml:space="preserve">'.xmlEsc($node->textContent).'</w:t></w:r>';
                    break;
                case 's': case 'del': case 'strike':
                    $out .= '<w:r><w:rPr><w:strike/></w:rPr><w:t xml:space="preserve">'.xmlEsc($node->textContent).'</w:t></w:r>';
                    break;
                case 'code':
                    $out .= '<w:r><w:rPr><w:rFonts w:ascii="Courier New" w:hAnsi="Courier New"/><w:sz w:val="18"/><w:color w:val="DC2626"/><w:shd w:val="clear" w:color="auto" w:fill="F1F5F9"/></w:rPr><w:t xml:space="preserve">'.xmlEsc($node->textContent).'</w:t></w:r>';
                    break;
                case 'a':
                    $out .= '<w:r><w:rPr><w:color w:val="F97316"/><w:u w:val="single"/></w:rPr><w:t xml:space="preserve">'.xmlEsc($node->textContent).'</w:t></w:r>';
                    break;
                case 'br':
                    $out .= '<w:br/>';
                    break;
                case 'span':
                    // Handle inline style colors etc
                    $style = $node->getAttribute('style') ?? '';
                    $color = '';
                    if (preg_match('/color\s*:\s*#?([0-9a-fA-F]{6})/i', $style, $m)) {
                        $color = '<w:color w:val="'.strtoupper($m[1]).'"/>';
                    }
                    $out .= '<w:r>'.($color?"<w:rPr>$color</w:rPr>":'').'<w:t xml:space="preserve">'.xmlEsc($node->textContent).'</w:t></w:r>';
                    break;
                default:
                    // Recurse
                    $out .= inlineToOoxml($node);
                    break;
            }
        }
    }
    return $out;
}

function tableToOoxml(DOMNode $table): string {
    $out  = '<w:tbl>';
    $out .= '<w:tblPr>';
    $out .= '<w:tblStyle w:val="TableGrid"/>';
    $out .= '<w:tblW w:w="0" w:type="auto"/>';
    $out .= '<w:tblLook w:val="04A0" w:firstRow="1" w:lastRow="0" w:firstColumn="1" w:lastColumn="0" w:noHBand="0" w:noVBand="1"/>';
    $out .= '</w:tblPr>';

    foreach ($table->childNodes as $child) {
        $cn = strtolower($child->nodeName);
        if ($cn === 'thead' || $cn === 'tbody' || $cn === 'tfoot') {
            foreach ($child->childNodes as $row) {
                if (strtolower($row->nodeName) === 'tr') {
                    $is_header = ($cn === 'thead');
                    $out .= trToOoxml($row, $is_header);
                }
            }
        } elseif ($cn === 'tr') {
            $out .= trToOoxml($child, false);
        }
    }
    $out .= '</w:tbl><w:p/>';
    return $out;
}

function trToOoxml(DOMNode $tr, bool $isHeader): string {
    $out = '<w:tr>';
    if ($isHeader) {
        $out .= '<w:trPr><w:tblHeader/><w:shd w:val="clear" w:color="auto" w:fill="F3F4F6"/></w:trPr>';
    }
    foreach ($tr->childNodes as $cell) {
        $cn = strtolower($cell->nodeName);
        if ($cn === 'td' || $cn === 'th') {
            $out .= '<w:tc><w:tcPr><w:tcBorders>';
            foreach (['top','left','bottom','right'] as $side) {
                $out .= '<w:'.$side.' w:val="single" w:sz="4" w:space="0" w:color="D1D5DB"/>';
            }
            $out .= '</w:tcBorders>';
            $out .= '<w:tcMar><w:top w:w="80" w:type="dxa"/><w:left w:w="120" w:type="dxa"/><w:bottom w:w="80" w:type="dxa"/><w:right w:w="120" w:type="dxa"/></w:tcMar>';
            $out .= '</w:tcPr>';
            $out .= '<w:p>';
            if ($cn === 'th' || $isHeader) {
                $out .= '<w:r><w:rPr><w:b/></w:rPr><w:t xml:space="preserve">'.xmlEsc(trim($cell->textContent)).'</w:t></w:r>';
            } else {
                $out .= inlineToOoxml($cell);
            }
            $out .= '</w:p></w:tc>';
        }
    }
    $out .= '</w:tr>';
    return $out;
}

function buildDocxStyles(): string {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
          xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml">
  <w:docDefaults>
    <w:rPrDefault><w:rPr>
      <w:rFonts w:ascii="Calibri" w:eastAsia="Calibri" w:hAnsi="Calibri"/>
      <w:sz w:val="22"/><w:szCs w:val="22"/>
    </w:rPr></w:rPrDefault>
    <w:pPrDefault><w:pPr>
      <w:spacing w:after="120" w:line="276" w:lineRule="auto"/>
    </w:pPr></w:pPrDefault>
  </w:docDefaults>

  <w:style w:type="paragraph" w:styleId="Normal" w:default="1">
    <w:name w:val="Normal"/>
    <w:pPr><w:spacing w:after="120"/></w:pPr>
  </w:style>

  <w:style w:type="paragraph" w:styleId="Title">
    <w:name w:val="Title"/>
    <w:pPr><w:spacing w:before="0" w:after="160"/></w:pPr>
    <w:rPr>
      <w:rFonts w:ascii="Calibri Light" w:hAnsi="Calibri Light"/>
      <w:sz w:val="52"/><w:szCs w:val="52"/>
      <w:color w:val="1F3864"/>
    </w:rPr>
  </w:style>

  <w:style w:type="paragraph" w:styleId="Subtitle">
    <w:name w:val="Subtitle"/>
    <w:pPr><w:spacing w:after="200"/></w:pPr>
    <w:rPr>
      <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
      <w:sz w:val="20"/><w:color w:val="666666"/><w:i/>
    </w:rPr>
  </w:style>

  <w:style w:type="paragraph" w:styleId="Heading1">
    <w:name w:val="heading 1"/>
    <w:pPr><w:spacing w:before="360" w:after="120"/><w:pBdr><w:bottom w:val="single" w:sz="4" w:space="1" w:color="E5E7EB"/></w:pBdr></w:pPr>
    <w:rPr><w:rFonts w:ascii="Calibri Light" w:hAnsi="Calibri Light"/><w:b/><w:sz w:val="36"/><w:color w:val="1a1a1a"/></w:rPr>
  </w:style>

  <w:style w:type="paragraph" w:styleId="Heading2">
    <w:name w:val="heading 2"/>
    <w:pPr><w:spacing w:before="280" w:after="80"/></w:pPr>
    <w:rPr><w:rFonts w:ascii="Calibri Light" w:hAnsi="Calibri Light"/><w:b/><w:sz w:val="28"/><w:color w:val="374151"/></w:rPr>
  </w:style>

  <w:style w:type="paragraph" w:styleId="Heading3">
    <w:name w:val="heading 3"/>
    <w:pPr><w:spacing w:before="240" w:after="60"/></w:pPr>
    <w:rPr><w:b/><w:sz w:val="24"/><w:color w:val="4B5563"/></w:rPr>
  </w:style>

  <w:style w:type="paragraph" w:styleId="Heading4">
    <w:name w:val="heading 4"/>
    <w:pPr><w:spacing w:before="160" w:after="40"/></w:pPr>
    <w:rPr><w:b/><w:sz w:val="22"/><w:color w:val="6B7280"/></w:rPr>
  </w:style>

  <w:style w:type="paragraph" w:styleId="ListParagraph">
    <w:name w:val="List Paragraph"/>
    <w:pPr><w:ind w:left="720"/><w:spacing w:after="60"/></w:pPr>
  </w:style>

  <w:style w:type="paragraph" w:styleId="Quote">
    <w:name w:val="Quote"/>
    <w:pPr><w:ind w:left="720"/><w:spacing w:before="80" w:after="80"/>
      <w:pBdr><w:left w:val="single" w:sz="12" w:space="4" w:color="F97316"/></w:pBdr>
    </w:pPr>
    <w:rPr><w:i/><w:color w:val="374151"/></w:rPr>
  </w:style>

  <w:style w:type="paragraph" w:styleId="CodeBlock">
    <w:name w:val="Code Block"/>
    <w:pPr><w:spacing w:after="0"/>
      <w:shd w:val="clear" w:color="auto" w:fill="1E293B"/>
    </w:pPr>
    <w:rPr><w:rFonts w:ascii="Courier New" w:hAnsi="Courier New"/><w:sz w:val="18"/><w:color w:val="E2E8F0"/></w:rPr>
  </w:style>

  <w:style w:type="table" w:styleId="TableGrid">
    <w:name w:val="Table Grid"/>
    <w:tblPr>
      <w:tblBorders>
        <w:top w:val="single" w:sz="4" w:space="0" w:color="D1D5DB"/>
        <w:left w:val="single" w:sz="4" w:space="0" w:color="D1D5DB"/>
        <w:bottom w:val="single" w:sz="4" w:space="0" w:color="D1D5DB"/>
        <w:right w:val="single" w:sz="4" w:space="0" w:color="D1D5DB"/>
        <w:insideH w:val="single" w:sz="4" w:space="0" w:color="D1D5DB"/>
        <w:insideV w:val="single" w:sz="4" w:space="0" w:color="D1D5DB"/>
      </w:tblBorders>
    </w:tblPr>
  </w:style>
</w:styles>';
}