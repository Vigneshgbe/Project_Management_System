<?php
/**
 * payslip_export.php — Payslip export: Print/PDF (browser) + DOCX
 */

function exportPayslip(mysqli $db, int $pid, string $fmt): void {
    $ps = $db->query("
        SELECT p.*, t.company_name, t.company_address, t.company_logo, t.footer_note
        FROM payslips p
        LEFT JOIN payslip_templates t ON t.id = p.template_id
        WHERE p.id = $pid
    ")->fetch_assoc();

    if (!$ps) { http_response_code(404); die('Payslip not found.'); }

    $allowances = json_decode($ps['allowances'] ?? '[]', true) ?: [];
    $deductions  = json_decode($ps['deductions']  ?? '[]', true) ?: [];
    $sym         = htmlspecialchars($ps['currency'] ?? 'LKR', ENT_QUOTES);
    $cname       = htmlspecialchars($ps['company_name'] ?? 'Company', ENT_QUOTES);
    $caddr       = htmlspecialchars($ps['company_address'] ?? '', ENT_QUOTES);
    $ename       = htmlspecialchars($ps['employee_name'], ENT_QUOTES);
    $period      = htmlspecialchars($ps['pay_period'], ENT_QUOTES);
    $pay_date_str = $ps['pay_date'] ? date('d M Y', strtotime($ps['pay_date'])) : '';

    // ──────────────────────────────────────────────
    // PRINT / PDF
    // ──────────────────────────────────────────────
    if ($fmt === 'print') {
        $logo_html = '';
        if ($ps['company_logo'] && file_exists($ps['company_logo'])) {
            $data = base64_encode(file_get_contents($ps['company_logo']));
            $mime = mime_content_type($ps['company_logo']) ?: 'image/png';
            $logo_html = "<img src='data:$mime;base64,$data' style='height:44px;margin-bottom:6px;display:block'>";
        }

        $earn_rows = "<tr style='border-bottom:1px solid #e2e8f0'><td style='padding:6px 8px'>Basic Salary</td><td style='padding:6px 8px;text-align:right;font-weight:600'>$sym " . number_format($ps['basic_salary'],2) . "</td></tr>";
        foreach ($allowances as $a) {
            $n = htmlspecialchars($a['name'], ENT_QUOTES);
            $earn_rows .= "<tr style='border-bottom:1px solid #f1f5f9'><td style='padding:6px 8px'>$n</td><td style='padding:6px 8px;text-align:right'>$sym " . number_format($a['amount'],2) . "</td></tr>";
        }

        $ded_rows = '';
        foreach ($deductions as $d) {
            $n = htmlspecialchars($d['name'], ENT_QUOTES);
            $ded_rows .= "<tr style='border-bottom:1px solid #f1f5f9'><td style='padding:6px 8px;color:#ef4444'>$n</td><td style='padding:6px 8px;text-align:right;color:#ef4444'>- $sym " . number_format($d['amount'],2) . "</td></tr>";
        }
        if (!$ded_rows) {
            $ded_rows = "<tr><td colspan='2' style='padding:8px;color:#94a3b8;font-size:12px'>No deductions for this period</td></tr>";
        }

        $desig_dept  = implode(' — ', array_filter([$ps['designation']??'', $ps['department']??'']));
        $footer_note = htmlspecialchars($ps['footer_note'] ?? 'This is a computer-generated payslip and requires no signature.', ENT_QUOTES);
        $notes_block = $ps['notes'] ? "<div style='background:#f8fafc;border-left:3px solid #e2e8f0;padding:10px 14px;margin:0 28px 18px;font-size:12px;color:#64748b'>".htmlspecialchars($ps['notes'],ENT_QUOTES)."</div>" : '';
        $status_col  = $ps['status'] === 'issued' ? '#10b981' : '#f59e0b';
        $gross_str   = "$sym " . number_format($ps['gross_salary'],   2);
        $ded_str     = "$sym " . number_format($ps['total_deductions'],2);
        $net_str     = "$sym " . number_format($ps['net_salary'],      2);

        header('Content-Type: text/html; charset=utf-8');
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Payslip — {$ename} — {$period}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#1e293b;background:#f1f5f9;}
.toolbar{background:#1e293b;color:#fff;padding:12px 24px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
.toolbar h3{font-size:14px;font-weight:600;}
.toolbar-btns{display:flex;gap:10px;}
.btn-print{background:#f97316;color:#fff;border:none;padding:9px 22px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:700;letter-spacing:.02em;}
.btn-print:hover{opacity:.88;}
.btn-close{background:rgba(255,255,255,.15);color:#fff;border:none;padding:9px 16px;border-radius:6px;cursor:pointer;font-size:13px;}
.page{background:#fff;max-width:750px;margin:24px auto;border-radius:8px;box-shadow:0 4px 24px rgba(0,0,0,.15);overflow:hidden;}
.hdr{background:#1e293b;padding:22px 28px;display:flex;justify-content:space-between;align-items:flex-start;}
.hdr-co h2{font-size:18px;font-weight:800;color:#fff;margin-bottom:3px;}
.hdr-co .addr{font-size:11px;color:rgba(255,255,255,.5);line-height:1.6;margin-top:4px;}
.hdr-right .ps-lbl{font-size:24px;font-weight:900;color:#f97316;letter-spacing:2px;}
.hdr-right .ps-meta{font-size:11.5px;color:rgba(255,255,255,.6);margin-top:6px;line-height:1.7;text-align:right;}
.body{padding:24px 28px;}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;padding-bottom:18px;border-bottom:2px solid #e2e8f0;margin-bottom:20px;}
.info-sec h4{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#64748b;margin-bottom:8px;}
.info-sec p{font-size:12.5px;color:#334155;line-height:1.6;margin:0;}
.sal-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:0;}
.sal-col h4{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#64748b;margin-bottom:10px;padding-bottom:6px;border-bottom:2px solid #e2e8f0;}
.sal-col table{width:100%;border-collapse:collapse;}
.sal-col td{font-size:12.5px;vertical-align:middle;}
.sal-total{display:flex;justify-content:space-between;padding:8px 0 0;font-weight:700;font-size:13px;border-top:2px solid #1e293b;margin-top:4px;}
.net-bar{background:#1e293b;padding:18px 28px;display:flex;justify-content:space-between;align-items:center;}
.net-bar .lbl{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.55);}
.net-bar .amt{font-size:28px;font-weight:900;color:#f97316;}
.footer-n{padding:12px 28px;background:#f8fafc;font-size:11px;color:#94a3b8;text-align:center;border-top:1px solid #e2e8f0;}
@media print{
    @page{margin:10mm;}
    .toolbar{display:none!important;}
    body{background:#fff;}
    .page{box-shadow:none;border-radius:0;margin:0;max-width:100%;}
}
</style>
</head>
<body>
<div class="toolbar no-print">
    <h3>📄 Payslip — {$ename} — {$period}</h3>
    <div class="toolbar-btns">
        <button class="btn-print" onclick="window.print()">🖨 Print / Save as PDF</button>
        <button class="btn-close" onclick="window.close()">✕ Close</button>
    </div>
</div>
<div class="page">
    <div class="hdr">
        <div class="hdr-co">
            {$logo_html}
            <h2>{$cname}</h2>
            <div class="addr">{$caddr}</div>
        </div>
        <div class="hdr-right">
            <div class="ps-lbl">PAYSLIP</div>
            <div class="ps-meta">
                Pay Period: <strong style="color:#fff">{$period}</strong><br>
                Pay Date: <strong style="color:#fff">{$pay_date_str}</strong><br>
                Status: <strong style="color:{$status_col}">{$ps['status']}</strong>
            </div>
        </div>
    </div>
    <div class="body">
        <div class="info-grid">
            <div class="info-sec">
                <h4>Employee Details</h4>
                <p><strong>{$ename}</strong></p>
                <p>{$desig_dept}</p>
                <p>EMP ID: {$ps['employee_id_no']}</p>
                <p>{$ps['employee_email']}</p>
                <p>{$ps['employee_phone']}</p>
            </div>
            <div class="info-sec">
                <h4>Bank Details</h4>
                <p>Bank: <strong>{$ps['bank_name']}</strong></p>
                <p>Account: <strong>{$ps['account_no']}</strong></p>
                <p style="margin-top:10px">Basic: <strong>$sym {$ps['basic_salary']}</strong></p>
                <p>Gross: <strong>$gross_str</strong></p>
                <p>Deductions: <strong style="color:#ef4444">- $ded_str</strong></p>
            </div>
        </div>
        {$notes_block}
        <div class="sal-grid">
            <div class="sal-col">
                <h4>Earnings</h4>
                <table>{$earn_rows}</table>
                <div class="sal-total"><span>Gross Salary</span><span>$gross_str</span></div>
            </div>
            <div class="sal-col">
                <h4>Deductions</h4>
                <table>{$ded_rows}</table>
                <div class="sal-total"><span>Total Deductions</span><span style="color:#ef4444">- $ded_str</span></div>
            </div>
        </div>
    </div>
    <div class="net-bar">
        <div>
            <div class="lbl">Net Salary Payable</div>
            <div style="font-size:12px;color:rgba(255,255,255,.4);margin-top:3px">{$period}</div>
        </div>
        <div class="amt">{$net_str}</div>
    </div>
    <div class="footer-n">{$footer_note}</div>
</div>
</body>
</html>
HTML;
        return;
    }

    // ──────────────────────────────────────────────
    // DOCX EXPORT
    // ──────────────────────────────────────────────
    if ($fmt === 'docx') {
        if (!class_exists('ZipArchive')) {
            http_response_code(500);
            die('ZipArchive not available. Please use Print/PDF instead.');
        }

        $en  = htmlspecialchars($ps['employee_name'],    ENT_XML1);
        $pr  = htmlspecialchars($ps['pay_period'],       ENT_XML1);
        $cn  = htmlspecialchars($ps['company_name']??'', ENT_XML1);
        $ca  = htmlspecialchars($ps['company_address']??'',ENT_XML1);
        $dg  = htmlspecialchars($ps['designation']??'',  ENT_XML1);
        $dp  = htmlspecialchars($ps['department']??'',   ENT_XML1);
        $ei  = htmlspecialchars($ps['employee_id_no']??'',ENT_XML1);
        $em  = htmlspecialchars($ps['employee_email']??'',ENT_XML1);
        $ph  = htmlspecialchars($ps['employee_phone']??'',ENT_XML1);
        $bk  = htmlspecialchars($ps['bank_name']??'',    ENT_XML1);
        $ac  = htmlspecialchars($ps['account_no']??'',   ENT_XML1);
        $ft  = htmlspecialchars($ps['footer_note']??'',  ENT_XML1);
        $nt  = htmlspecialchars($ps['notes']??'',        ENT_XML1);
        $st  = ucfirst($ps['status']);
        $cur = $ps['currency'] ?? 'LKR';
        $gross_s = "$cur " . number_format($ps['gross_salary'],    2);
        $ded_s   = "$cur " . number_format($ps['total_deductions'],2);
        $net_s   = "$cur " . number_format($ps['net_salary'],      2);
        $basic_s = "$cur " . number_format($ps['basic_salary'],    2);

        // Build earnings rows XML
        $earn_xml = "<w:tr><w:tc><w:p><w:r><w:rPr><w:b/><w:color w:val='1e293b'/></w:rPr><w:t>Basic Salary</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:jc w:val='right'/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>$basic_s</w:t></w:r></w:p></w:tc></w:tr>";
        foreach ($allowances as $a) {
            $n = htmlspecialchars($a['name'], ENT_XML1);
            $v = "$cur " . number_format($a['amount'], 2);
            $earn_xml .= "<w:tr><w:tc><w:p><w:r><w:t>$n</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:jc w:val='right'/></w:pPr><w:r><w:t>$v</w:t></w:r></w:p></w:tc></w:tr>";
        }

        $ded_xml = '';
        foreach ($deductions as $d) {
            $n = htmlspecialchars($d['name'], ENT_XML1);
            $v = "$cur " . number_format($d['amount'], 2);
            $ded_xml .= "<w:tr><w:tc><w:p><w:r><w:rPr><w:color w:val='EF4444'/></w:rPr><w:t>$n</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:jc w:val='right'/></w:pPr><w:r><w:rPr><w:color w:val='EF4444'/></w:rPr><w:t>- $v</w:t></w:r></w:p></w:tc></w:tr>";
        }
        if (!$ded_xml) $ded_xml = "<w:tr><w:tc><w:p><w:r><w:rPr><w:color w:val='94A3B8'/></w:rPr><w:t>No deductions</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t></w:t></w:r></w:p></w:tc></w:tr>";

        $notes_xml = $nt ? "<w:p><w:pPr><w:spacing w:before='120' w:after='60'/></w:pPr><w:r><w:rPr><w:i/><w:color w:val='64748b'/></w:rPr><w:t>Note: $nt</w:t></w:r></w:p>" : '';

        // Shared table border props
        $tbdr = '<w:tblBorders><w:top w:val="single" w:sz="4" w:color="E2E8F0"/><w:left w:val="single" w:sz="4" w:color="E2E8F0"/><w:bottom w:val="single" w:sz="4" w:color="E2E8F0"/><w:right w:val="single" w:sz="4" w:color="E2E8F0"/><w:insideH w:val="single" w:sz="4" w:color="E2E8F0"/><w:insideV w:val="single" w:sz="4" w:color="E2E8F0"/></w:tblBorders>';

        $doc_xml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
<w:body>
<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="0"/></w:pPr>
  <w:r><w:rPr><w:b/><w:sz w:val="40"/><w:color w:val="F97316"/></w:rPr><w:t>PAYSLIP</w:t></w:r></w:p>
<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="60"/></w:pPr>
  <w:r><w:rPr><w:b/><w:sz w:val="28"/><w:color w:val="1E293B"/></w:rPr><w:t>$cn</w:t></w:r></w:p>
<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="200"/></w:pPr>
  <w:r><w:rPr><w:sz w:val="20"/><w:color w:val="64748B"/></w:rPr><w:t>Pay Period: $pr  |  Pay Date: $pay_date_str  |  Status: $st</w:t></w:r></w:p>
<w:p><w:pPr><w:spacing w:before="100" w:after="60"/></w:pPr>
  <w:r><w:rPr><w:b/><w:color w:val="1E293B"/><w:sz w:val="24"/></w:rPr><w:t>EMPLOYEE DETAILS</w:t></w:r></w:p>
<w:tbl>
  <w:tblPr><w:tblW w:type="pct" w:w="5000"/>$tbdr</w:tblPr>
  <w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Name</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>$en</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Designation</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>$dg</w:t></w:r></w:p></w:tc></w:tr>
  <w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Department</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>$dp</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Employee ID</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>$ei</w:t></w:r></w:p></w:tc></w:tr>
  <w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Email</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>$em</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Phone</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>$ph</w:t></w:r></w:p></w:tc></w:tr>
  <w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Bank</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>$bk</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Account No.</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>$ac</w:t></w:r></w:p></w:tc></w:tr>
</w:tbl>
<w:p><w:pPr><w:spacing w:before="200" w:after="60"/></w:pPr>
  <w:r><w:rPr><w:b/><w:color w:val="1E293B"/><w:sz w:val="24"/></w:rPr><w:t>EARNINGS</w:t></w:r></w:p>
<w:tbl>
  <w:tblPr><w:tblW w:type="pct" w:w="5000"/>$tbdr</w:tblPr>
  $earn_xml
  <w:tr><w:tc><w:tcPr><w:shd w:fill="1E293B" w:val="clear"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/></w:rPr><w:t>Gross Salary</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:shd w:fill="1E293B" w:val="clear"/></w:tcPr><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="F97316"/></w:rPr><w:t>$gross_s</w:t></w:r></w:p></w:tc></w:tr>
</w:tbl>
<w:p><w:pPr><w:spacing w:before="200" w:after="60"/></w:pPr>
  <w:r><w:rPr><w:b/><w:color w:val="1E293B"/><w:sz w:val="24"/></w:rPr><w:t>DEDUCTIONS</w:t></w:r></w:p>
<w:tbl>
  <w:tblPr><w:tblW w:type="pct" w:w="5000"/>$tbdr</w:tblPr>
  $ded_xml
  <w:tr><w:tc><w:tcPr><w:shd w:fill="1E293B" w:val="clear"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/></w:rPr><w:t>Total Deductions</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:shd w:fill="1E293B" w:val="clear"/></w:tcPr><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="EF4444"/></w:rPr><w:t>- $ded_s</w:t></w:r></w:p></w:tc></w:tr>
</w:tbl>
$notes_xml
<w:p><w:pPr><w:spacing w:before="240" w:after="60"/></w:pPr>
  <w:r><w:rPr><w:b/><w:sz w:val="32"/><w:color w:val="F97316"/></w:rPr><w:t>NET SALARY PAYABLE: $net_s</w:t></w:r></w:p>
<w:p><w:pPr><w:spacing w:before="300" w:after="0"/><w:jc w:val="center"/></w:pPr>
  <w:r><w:rPr><w:sz w:val="18"/><w:color w:val="94A3B8"/></w:rPr><w:t>$ft</w:t></w:r></w:p>
<w:sectPr><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720"/></w:sectPr>
</w:body>
</w:document>
XML;

        $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $ps['employee_name']);
        $safe_per  = preg_replace('/\s+/', '_', $ps['pay_period']);
        $fname     = "Payslip_{$safe_name}_{$safe_per}.docx";

        $zip = new ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'ps_') . '.docx';
        if ($zip->open($tmp, ZipArchive::CREATE) !== true) {
            http_response_code(500); die('Cannot create DOCX file.');
        }
        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $zip->addFromString('word/_rels/document.xml.rels',
            '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>');
        $zip->addFromString('word/document.xml', $doc_xml);
        $zip->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="'.$fname.'"');
        header('Content-Length: '.filesize($tmp));
        header('Cache-Control: no-cache');
        readfile($tmp);
        unlink($tmp);
    }
}