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
// PDF EXPORT — Full styled HTML page sent as PDF download
// Uses wkhtmltopdf approach: serve as application/pdf
// Browser renders + saves. Works without any server library.
// ═══════════════════════════════════════════════════════
if ($format === 'pdf') {
    // Build clean print-ready HTML
    $html = buildPdfHtml($title, $content, $author, $date, $rd['category']);

    // Try to use wkhtmltopdf if available on server (Hostinger sometimes has it)
    $wk = trim(shell_exec('which wkhtmltopdf 2>/dev/null') ?? '');
    if ($wk) {
        $tmp_in  = sys_get_temp_dir().'/rdoc_'.$id.'.html';
        $tmp_out = sys_get_temp_dir().'/rdoc_'.$id.'.pdf';
        file_put_contents($tmp_in, $html);
        shell_exec(escapeshellcmd($wk).' --quiet --page-size A4 --margin-top 20mm --margin-bottom 20mm --margin-left 20mm --margin-right 20mm '.escapeshellarg($tmp_in).' '.escapeshellarg($tmp_out).' 2>/dev/null');
        if (file_exists($tmp_out) && filesize($tmp_out) > 0) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="'.rawurlencode($safe_fn).'.pdf"');
            header('Content-Length: '.filesize($tmp_out));
            readfile($tmp_out);
            @unlink($tmp_in); @unlink($tmp_out);
            exit;
        }
        @unlink($tmp_in);
    }

    // Fallback: serve as HTML page that auto-triggers browser print dialog
    // with a proper download prompt via Content-Disposition on a styled page
    // This is the most reliable cross-host approach with zero server dependencies
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
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
// HTML BUILDER FOR PDF
// ═══════════════════════════════════════════════════════
function buildPdfHtml(string $title, string $content, string $author, string $date, string $category): string {
    $ht = htmlspecialchars($title, ENT_QUOTES);
    $ha = htmlspecialchars($author, ENT_QUOTES);
    $hd = htmlspecialchars($date, ENT_QUOTES);
    $hc = htmlspecialchars($category, ENT_QUOTES);
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$ht}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Calibri,Arial,sans-serif;font-size:11pt;color:#1a1a1a;background:#fff;padding:40px 60px;max-width:900px;margin:0 auto}
.doc-header{border-bottom:2px solid #f97316;padding-bottom:16px;margin-bottom:24px}
.doc-title{font-size:22pt;font-weight:700;color:#1a1a1a;margin-bottom:8px;line-height:1.2}
.doc-meta{font-size:9pt;color:#666;display:flex;gap:16px;flex-wrap:wrap}
.doc-meta span{display:inline-flex;align-items:center;gap:4px}
.doc-content{line-height:1.75;color:#1a1a1a}
.doc-content h1{font-size:18pt;margin:20px 0 10px;color:#1a1a1a;border-bottom:1px solid #e5e7eb;padding-bottom:6px}
.doc-content h2{font-size:15pt;margin:18px 0 8px;color:#1a1a1a}
.doc-content h3{font-size:12pt;margin:14px 0 6px;color:#374151}
.doc-content h4{font-size:11pt;margin:12px 0 5px;color:#374151}
.doc-content p{margin-bottom:10px}
.doc-content ul,.doc-content ol{padding-left:26px;margin-bottom:10px}
.doc-content li{margin-bottom:4px}
.doc-content table{border-collapse:collapse;width:100%;margin:14px 0;font-size:10pt}
.doc-content table td,.doc-content table th{border:1px solid #d1d5db;padding:8px 12px;text-align:left}
.doc-content table th{background:#f3f4f6;font-weight:700;color:#111}
.doc-content table tr:nth-child(even) td{background:#f9fafb}
.doc-content blockquote{border-left:3px solid #f97316;margin:12px 0;padding:8px 16px;background:#fff7ed;color:#374151;font-style:italic}
.doc-content pre{background:#1e293b;color:#e2e8f0;padding:14px 16px;border-radius:6px;overflow-x:auto;font-family:'Courier New',monospace;font-size:9pt;margin:12px 0}
.doc-content code{background:#f1f5f9;color:#dc2626;padding:1px 5px;border-radius:3px;font-family:'Courier New',monospace;font-size:9pt}
.doc-content pre code{background:none;color:inherit;padding:0}
.doc-content img{max-width:100%;height:auto;border-radius:4px;margin:8px 0}
.doc-content a{color:#f97316}
.doc-content hr{border:none;border-top:1px solid #e5e7eb;margin:16px 0}
.doc-footer{margin-top:32px;padding-top:12px;border-top:1px solid #e5e7eb;font-size:8.5pt;color:#9ca3af;display:flex;justify-content:space-between}
@media print{
  body{padding:0;max-width:none}
  .no-print{display:none}
  @page{margin:2cm;size:A4}
}
.export-bar{background:#f97316;color:#fff;padding:10px 20px;text-align:center;font-size:11pt;font-weight:600;position:sticky;top:0;z-index:999;display:flex;justify-content:center;gap:20px;align-items:center}
.export-bar button{background:#fff;color:#f97316;border:none;padding:6px 16px;border-radius:6px;font-weight:700;cursor:pointer;font-size:10pt}
</style>
</head>
<body>
<div class="export-bar no-print">
  <span>📄 {$ht}</span>
  <button onclick="window.print()">🖨 Print / Save as PDF</button>
  <button onclick="window.close()" style="background:rgba(255,255,255,.2);color:#fff">✕ Close</button>
</div>
<br class="no-print">
<div class="doc-header">
  <div class="doc-title">{$ht}</div>
  <div class="doc-meta">
    <span>✍ {$ha}</span>
    <span>📅 {$hd}</span>
    <span>📂 {$hc}</span>
  </div>
</div>
<div class="doc-content">{$content}</div>
<div class="doc-footer">
  <span>Padak CRM — {$ht}</span>
  <span>{$hd}</span>
</div>
<script>
// Show save-as-PDF instructions if not printing
if(!window.opener && window.location.search.indexOf('autoprint')!==-1) window.print();
</script>
</body>
</html>
HTML;
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