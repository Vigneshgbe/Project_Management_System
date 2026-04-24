<?php
/**
 * payslip_export.php — Payslip export: print/PDF + DOCX
 * Called as: require_once 'payslip_export.php'; exportPayslip($db,$id,$fmt);
 */

function exportPayslip(mysqli $db, int $pid, string $fmt): void {
    $ps = $db->query("
        SELECT p.*, t.company_name, t.company_address, t.company_logo, t.footer_note
        FROM payslips p
        LEFT JOIN payslip_templates t ON t.id=p.template_id
        WHERE p.id=$pid
    ")->fetch_assoc();

    if (!$ps) { http_response_code(404); die('Payslip not found.'); }

    $allowances = json_decode($ps['allowances'] ?? '[]', true) ?: [];
    $deductions  = json_decode($ps['deductions']  ?? '[]', true) ?: [];
    $sym         = htmlspecialchars($ps['currency'] ?? 'LKR', ENT_QUOTES);
    $cname       = htmlspecialchars($ps['company_name'] ?? 'Company', ENT_QUOTES);
    $caddr       = htmlspecialchars($ps['company_address'] ?? '', ENT_QUOTES);
    $ename       = htmlspecialchars($ps['employee_name'], ENT_QUOTES);
    $period      = htmlspecialchars($ps['pay_period'], ENT_QUOTES);

    // ── PRINT / PDF ──
    if ($fmt === 'print') {
        $logo_html = '';
        if ($ps['company_logo'] && file_exists($ps['company_logo'])) {
            $data = base64_encode(file_get_contents($ps['company_logo']));
            $mime = mime_content_type($ps['company_logo']) ?: 'image/png';
            $logo_html = "<img src='data:$mime;base64,$data' style='height:44px;margin-bottom:6px;display:block'>";
        }

        $allow_rows = "<tr><td style='padding:5px 8px;border-bottom:1px solid #f1f5f9'>Basic Salary</td><td style='padding:5px 8px;border-bottom:1px solid #f1f5f9;text-align:right;font-weight:600'>$sym " . number_format($ps['basic_salary'],2) . "</td></tr>";
        foreach ($allowances as $a) {
            $n = htmlspecialchars($a['name'],ENT_QUOTES);
            $allow_rows .= "<tr><td style='padding:5px 8px;border-bottom:1px solid #f1f5f9'>$n</td><td style='padding:5px 8px;border-bottom:1px solid #f1f5f9;text-align:right'>$sym " . number_format($a['amount'],2) . "</td></tr>";
        }
        $ded_rows = '';
        foreach ($deductions as $d) {
            $n = htmlspecialchars($d['name'],ENT_QUOTES);
            $ded_rows .= "<tr><td style='padding:5px 8px;border-bottom:1px solid #f1f5f9'>$n</td><td style='padding:5px 8px;border-bottom:1px solid #f1f5f9;text-align:right;color:#ef4444'>- $sym " . number_format($d['amount'],2) . "</td></tr>";
        }
        if (!$ded_rows) $ded_rows = "<tr><td colspan='2' style='padding:8px;color:#94a3b8;font-size:12px'>No deductions</td></tr>";

        $pay_date_str = $ps['pay_date'] ? date('d M Y', strtotime($ps['pay_date'])) : '';
        $notes_block = $ps['notes'] ? "<div style='margin-top:14px;padding:10px 12px;background:#f8fafc;border-radius:4px;font-size:12px;color:#64748b'><strong>Note:</strong> ".htmlspecialchars($ps['notes'],ENT_QUOTES)."</div>" : '';
        $desig_dept   = trim(($ps['designation']??'') . ($ps['department']?' — '.$ps['department']:''));
        $footer_note  = htmlspecialchars($ps['footer_note'] ?? 'This is a computer-generated payslip.', ENT_QUOTES);

        header('Content-Type: text/html; charset=utf-8');
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Payslip - {$ename} - {$period}</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, Helvetica, sans-serif; font-size:13px; color:#1a1a1a; background:#f8fafc; }
  .page { background:#fff; max-width:720px; margin:20px auto; padding:0; box-shadow:0 2px 20px rgba(0,0,0,.12); border-radius:8px; overflow:hidden; }
  .hdr { background:#1e293b; color:#fff; padding:20px 28px; display:flex; justify-content:space-between; align-items:flex-start; }
  .hdr h2 { font-size:18px; color:#fff; margin-bottom:2px; }
  .hdr .addr { font-size:11px; color:rgba(255,255,255,.6); margin-top:4px; }
  .hdr-right { text-align:right; }
  .hdr-right .ps-label { font-size:22px; font-weight:900; color:#f97316; letter-spacing:2px; }
  .hdr-right .ps-meta { font-size:11px; color:rgba(255,255,255,.65); margin-top:4px; }
  .body { padding:24px 28px; }
  .emp-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; padding-bottom:16px; border-bottom:2px solid #e2e8f0; }
  .sec-title { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#64748b; margin-bottom:8px; }
  .emp-name { font-size:15px; font-weight:700; margin-bottom:4px; }
  .emp-meta { font-size:12px; color:#64748b; line-height:1.6; }
  .salary-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
  .salary-table { width:100%; border-collapse:collapse; }
  .salary-table th { background:#f8fafc; padding:7px 8px; text-align:left; font-size:10px; text-transform:uppercase; letter-spacing:.05em; color:#64748b; border-bottom:2px solid #e2e8f0; }
  .total-row td { padding:8px; font-weight:700; border-top:2px solid #1e293b; font-size:13px; }
  .divider { width:1px; background:#e2e8f0; }
  .net-bar { background:#1e293b; color:#fff; padding:16px 28px; display:flex; justify-content:space-between; align-items:center; margin-top:20px; border-radius:0 0 8px 8px; }
  .net-bar .lbl { font-size:12px; opacity:.7; }
  .net-bar .amt { font-size:26px; font-weight:900; color:#f97316; }
  .footer-note { font-size:11px; color:#94a3b8; text-align:center; padding:12px 28px 16px; background:#f8fafc; }
  @media print {
    body { background:#fff; }
    .page { box-shadow:none; margin:0; border-radius:0; }
    .no-print { display:none !important; }
  }
</style>
</head>
<body>
<div class="no-print" style="background:#1e293b;color:#fff;padding:12px 20px;display:flex;align-items:center;justify-content:space-between">
  <span style="font-size:14px;font-weight:600">Payslip Preview — {$ename}</span>
  <div style="display:flex;gap:10px">
    <button onclick="window.print()" style="background:#f97316;color:#fff;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:700">🖨 Print / Save as PDF</button>
    <button onclick="window.close()" style="background:rgba(255,255,255,.15);color:#fff;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;font-size:13px">✕ Close</button>
  </div>
</div>
<div class="page">
  <div class="hdr">
    <div>{$logo_html}<h2>{$cname}</h2><div class="addr">{$caddr}</div></div>
    <div class="hdr-right">
      <div class="ps-label">PAYSLIP</div>
      <div class="ps-meta">Pay Period: {$period}</div>
      <div class="ps-meta">Pay Date: {$pay_date_str}</div>
    </div>
  </div>
  <div class="body">
    <div class="emp-grid">
      <div>
        <div class="sec-title">Employee Details</div>
        <div class="emp-name">{$ename}</div>
        <div class="emp-meta">
          {$desig_dept}<br>
          Emp ID: {$ps['employee_id_no']}<br>
          {$ps['employee_email']}<br>
          {$ps['employee_phone']}
        </div>
      </div>
      <div>
        <div class="sec-title">Bank Details</div>
        <div class="emp-meta">
          Bank: {$ps['bank_name']}<br>
          Account: {$ps['account_no']}<br>
          <br>
          <strong>Status: <span style="color:#10b981">{$ps['status']}</span></strong>
        </div>
      </div>
    </div>
    <div class="salary-grid">
      <div>
        <div class="sec-title">Earnings</div>
        <table class="salary-table">
          <thead><tr><th>Description</th><th style="text-align:right">Amount</th></tr></thead>
          <tbody>{$allow_rows}</tbody>
          <tfoot><tr class="total-row"><td>Gross Salary</td><td style="text-align:right">$sym {$ps['gross_salary']}</td></tr></tfoot>
        </table>
      </div>
      <div>
        <div class="sec-title">Deductions</div>
        <table class="salary-table">
          <thead><tr><th>Description</th><th style="text-align:right">Amount</th></tr></thead>
          <tbody>{$ded_rows}</tbody>
          <tfoot><tr class="total-row"><td>Total Deductions</td><td style="text-align:right">$sym {$ps['total_deductions']}</td></tr></tfoot>
        </table>
      </div>
    </div>
    {$notes_block}
  </div>
  <div class="net-bar">
    <div><div class="lbl">Net Salary Payable</div><div class="lbl">{$period}</div></div>
    <div class="amt">{$sym} {$ps['net_salary']}</div>
  </div>
  <div class="footer-note">{$footer_note}</div>
</div>
<script>
// Auto-trigger browser print dialog
window.onload = function() {
  // Small delay so page renders first
  setTimeout(function(){ window.print(); }, 500);
};
</script>
</body>
</html>
HTML;
        return;
    }

    // ── DOCX EXPORT ──
    if ($fmt === 'docx') {
        $allow_rows_docx = "<w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Basic Salary</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:jc w:val='right'/></w:pPr><w:r><w:t>$sym " . number_format($ps['basic_salary'],2) . "</w:t></w:r></w:p></w:tc></w:tr>";
        foreach ($allowances as $a) {
            $n = htmlspecialchars($a['name'],ENT_QUOTES);
            $v = number_format($a['amount'],2);
            $allow_rows_docx .= "<w:tr><w:tc><w:p><w:r><w:t>$n</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:jc w:val='right'/></w:pPr><w:r><w:t>$sym $v</w:t></w:r></w:p></w:tc></w:tr>";
        }

        $ded_rows_docx = '';
        foreach ($deductions as $d) {
            $n = htmlspecialchars($d['name'],ENT_QUOTES);
            $v = number_format($d['amount'],2);
            $ded_rows_docx .= "<w:tr><w:tc><w:p><w:r><w:t>$n</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:jc w:val='right'/></w:pPr><w:r><w:t>- $sym $v</w:t></w:r></w:p></w:tc></w:tr>";
        }
        if (!$ded_rows_docx) $ded_rows_docx = "<w:tr><w:tc><w:p><w:r><w:t>No deductions</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t></w:t></w:r></w:p></w:tc></w:tr>";

        $emp_name_raw  = htmlspecialchars($ps['employee_name'],ENT_XML1);
        $period_raw    = htmlspecialchars($ps['pay_period'],ENT_XML1);
        $cname_raw     = htmlspecialchars($ps['company_name']??'',ENT_XML1);
        $desig_raw     = htmlspecialchars($ps['designation']??'',ENT_XML1);
        $dept_raw      = htmlspecialchars($ps['department']??'',ENT_XML1);
        $empid_raw     = htmlspecialchars($ps['employee_id_no']??'',ENT_XML1);
        $bank_raw      = htmlspecialchars($ps['bank_name']??'',ENT_XML1);
        $acct_raw      = htmlspecialchars($ps['account_no']??'',ENT_XML1);
        $gross_str     = "$sym " . number_format($ps['gross_salary'],2);
        $ded_str       = "$sym " . number_format($ps['total_deductions'],2);
        $net_str       = "$sym " . number_format($ps['net_salary'],2);
        $pay_date_str2 = $ps['pay_date'] ? date('d M Y',strtotime($ps['pay_date'])) : '';
        $footer_raw    = htmlspecialchars($ps['footer_note']??'',ENT_XML1);
        $notes_raw     = htmlspecialchars($ps['notes']??'',ENT_XML1);

        $notes_docx_block = $notes_raw ? "<w:p><w:pPr><w:spacing w:before=\"120\" w:after=\"60\"/></w:pPr><w:r><w:rPr><w:i/><w:color w:val=\"64748b\"/></w:rPr><w:t>Note: $notes_raw</w:t></w:r></w:p>" : '';
        $doc_xml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas"
  xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
  xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<w:body>
<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="0"/></w:pPr>
  <w:r><w:rPr><w:b/><w:sz w:val="36"/><w:color w:val="F97316"/></w:rPr><w:t>$cname_raw — PAYSLIP</w:t></w:r></w:p>
<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="120"/></w:pPr>
  <w:r><w:rPr><w:sz w:val="22"/><w:color w:val="64748b"/></w:rPr><w:t>Pay Period: $period_raw  |  Pay Date: $pay_date_str2</w:t></w:r></w:p>
<w:p><w:pPr><w:spacing w:before="120" w:after="60"/></w:pPr>
  <w:r><w:rPr><w:b/><w:color w:val="1e293b"/></w:rPr><w:t>EMPLOYEE DETAILS</w:t></w:r></w:p>
<w:tbl>
  <w:tblPr><w:tblW w:type="pct" w:w="5000"/><w:tblBorders><w:top w:val="single" w:sz="4" w:color="E2E8F0"/><w:left w:val="single" w:sz="4" w:color="E2E8F0"/><w:bottom w:val="single" w:sz="4" w:color="E2E8F0"/><w:right w:val="single" w:sz="4" w:color="E2E8F0"/><w:insideH w:val="single" w:sz="4" w:color="E2E8F0"/><w:insideV w:val="single" w:sz="4" w:color="E2E8F0"/></w:tblBorders></w:tblPr>
  <w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Name</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>$emp_name_raw</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Designation</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>$desig_raw</w:t></w:r></w:p></w:tc></w:tr>
  <w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Department</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>$dept_raw</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Employee ID</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>$empid_raw</w:t></w:r></w:p></w:tc></w:tr>
  <w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Bank</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>$bank_raw</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Account</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>$acct_raw</w:t></w:r></w:p></w:tc></w:tr>
</w:tbl>
<w:p><w:pPr><w:spacing w:before="160" w:after="60"/></w:pPr>
  <w:r><w:rPr><w:b/><w:color w:val="1e293b"/></w:rPr><w:t>EARNINGS</w:t></w:r></w:p>
<w:tbl>
  <w:tblPr><w:tblW w:type="pct" w:w="5000"/><w:tblBorders><w:top w:val="single" w:sz="4" w:color="E2E8F0"/><w:left w:val="single" w:sz="4" w:color="E2E8F0"/><w:bottom w:val="single" w:sz="4" w:color="E2E8F0"/><w:right w:val="single" w:sz="4" w:color="E2E8F0"/><w:insideH w:val="single" w:sz="4" w:color="E2E8F0"/><w:insideV w:val="single" w:sz="4" w:color="E2E8F0"/></w:tblBorders></w:tblPr>
  {$allow_rows_docx}
  <w:tr><w:tc><w:tcPr><w:shd w:fill="1e293b" w:val="clear"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/></w:rPr><w:t>Gross Salary</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:shd w:fill="1e293b" w:val="clear"/></w:tcPr><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="F97316"/></w:rPr><w:t>$gross_str</w:t></w:r></w:p></w:tc></w:tr>
</w:tbl>
<w:p><w:pPr><w:spacing w:before="160" w:after="60"/></w:pPr>
  <w:r><w:rPr><w:b/><w:color w:val="1e293b"/></w:rPr><w:t>DEDUCTIONS</w:t></w:r></w:p>
<w:tbl>
  <w:tblPr><w:tblW w:type="pct" w:w="5000"/><w:tblBorders><w:top w:val="single" w:sz="4" w:color="E2E8F0"/><w:left w:val="single" w:sz="4" w:color="E2E8F0"/><w:bottom w:val="single" w:sz="4" w:color="E2E8F0"/><w:right w:val="single" w:sz="4" w:color="E2E8F0"/><w:insideH w:val="single" w:sz="4" w:color="E2E8F0"/><w:insideV w:val="single" w:sz="4" w:color="E2E8F0"/></w:tblBorders></w:tblPr>
  {$ded_rows_docx}
  <w:tr><w:tc><w:tcPr><w:shd w:fill="1e293b" w:val="clear"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/></w:rPr><w:t>Total Deductions</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:shd w:fill="1e293b" w:val="clear"/></w:tcPr><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="EF4444"/></w:rPr><w:t>$ded_str</w:t></w:r></w:p></w:tc></w:tr>
</w:tbl>
<w:p><w:pPr><w:spacing w:before="200" w:after="60"/></w:pPr>
  <w:r><w:rPr><w:b/><w:sz w:val="28"/><w:color w:val="F97316"/></w:rPr><w:t>NET SALARY PAYABLE: $net_str</w:t></w:r></w:p>
{$notes_docx_block}
<w:p><w:pPr><w:spacing w:before="200" w:after="0"/><w:jc w:val="center"/></w:pPr>
  <w:r><w:rPr><w:sz w:val="18"/><w:color w:val="94a3b8"/></w:rPr><w:t>$footer_raw</w:t></w:r></w:p>
<w:sectPr><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720"/></w:sectPr>
</w:body>
</w:document>
XML;

        $fname = 'Payslip_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $ps['employee_name']) . '_' . preg_replace('/\s+/', '_', $ps['pay_period']) . '.docx';

        // Build DOCX (ZIP with OOXML structure)
        $zip = new ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'payslip_') . '.docx';
        $zip->open($tmp, ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $zip->addFromString('word/document.xml', $doc_xml);
        $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>');
        $zip->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        return;
    }
}