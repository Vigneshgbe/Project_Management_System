<?php
/**
 * payslip_export.php — Professional payslip export: Print/PDF + DOCX
 * Legally compliant with Sri Lanka Shop & Office Act / EPF / ETF requirements
 */

function exportPayslip(mysqli $db, int $pid, string $fmt): void {

    $ps = $db->query("
        SELECT p.*,
               t.company_name, t.company_reg_no, t.company_phone, t.company_email,
               t.company_address, t.company_logo, t.footer_note,
               t.epf_employer_no, t.authorized_by, t.authorized_title
        FROM payslips p
        LEFT JOIN payslip_templates t ON t.id = p.template_id
        WHERE p.id = $pid
    ")->fetch_assoc();

    if (!$ps) { http_response_code(404); die('Payslip not found.'); }

    $allowances  = json_decode($ps['allowances'] ?? '[]', true) ?: [];
    $deductions  = json_decode($ps['deductions']  ?? '[]', true) ?: [];
    $sym         = htmlspecialchars($ps['currency'] ?? 'LKR', ENT_QUOTES);
    $cname       = htmlspecialchars($ps['company_name']    ?? 'Company',    ENT_QUOTES);
    $creg        = htmlspecialchars($ps['company_reg_no']  ?? '',           ENT_QUOTES);
    $cphone      = htmlspecialchars($ps['company_phone']   ?? '',           ENT_QUOTES);
    $cemail      = htmlspecialchars($ps['company_email']   ?? '',           ENT_QUOTES);
    $caddr       = htmlspecialchars($ps['company_address'] ?? '',           ENT_QUOTES);
    $epf_eno     = htmlspecialchars($ps['epf_employer_no'] ?? '',           ENT_QUOTES);
    $auth_by     = htmlspecialchars($ps['authorized_by']   ?? '',           ENT_QUOTES);
    $auth_ttl    = htmlspecialchars($ps['authorized_title']?? 'HR / Finance Department', ENT_QUOTES);
    $ename       = htmlspecialchars($ps['employee_name'],                   ENT_QUOTES);
    $period      = htmlspecialchars($ps['pay_period'],                      ENT_QUOTES);
    $ps_ref      = htmlspecialchars($ps['payslip_ref'] ?? ('PS-'.$pid),    ENT_QUOTES);
    $nic_no      = htmlspecialchars($ps['nic_number']  ?? '',               ENT_QUOTES);
    $epf_mno     = htmlspecialchars($ps['epf_member_no'] ?? '',             ENT_QUOTES);
    $pay_date_str= $ps['pay_date'] ? date('d M Y', strtotime($ps['pay_date'])) : '—';
    $issue_date  = date('d M Y');

    // Days calculations
    $wdays       = (int)($ps['working_days'] ?? 0);
    $ddays       = (int)($ps['days_paid']    ?? 0);
    $absent_days = ($wdays && $ddays) ? $wdays - $ddays : 0;
    $daily_rate  = ($wdays && $ps['basic_salary']) ? number_format($ps['basic_salary'] / $wdays, 2) : '—';

    // ──────────────────────────────────────────────────────────────────────
    // PRINT / PDF
    // ──────────────────────────────────────────────────────────────────────
    if ($fmt === 'print') {

        // Logo as base64
        $logo_html = '';
        if (!empty($ps['company_logo']) && file_exists($ps['company_logo'])) {
            $data = base64_encode(file_get_contents($ps['company_logo']));
            $mime = mime_content_type($ps['company_logo']) ?: 'image/png';
            $logo_html = "<img src='data:{$mime};base64,{$data}' style='height:48px;margin-bottom:8px;display:block;border-radius:4px'>";
        }

        // Company sub-info block
        $co_sub = '';
        if ($creg)    $co_sub .= "<div class='co-sub'>Reg. No: {$creg}</div>";
        if ($caddr)   $co_sub .= "<div class='co-sub'>".nl2br($caddr)."</div>";
        if ($cphone || $cemail) {
            $parts = array_filter([$cphone, $cemail]);
            $co_sub .= "<div class='co-sub'>".implode('&nbsp;&nbsp;·&nbsp;&nbsp;', $parts)."</div>";
        }
        if ($epf_eno) $co_sub .= "<div class='co-sub'>EPF Employer No: {$epf_eno}</div>";

        // Status colour
        $status_col = $ps['status'] === 'issued' ? '#10b981' : '#f59e0b';

        // Notes block
        $notes_block = $ps['notes']
            ? "<div class='notes-block'>📋 ".htmlspecialchars($ps['notes'],ENT_QUOTES)."</div>"
            : '';

        // Days bar HTML
        $days_html = '';
        if ($wdays || $ddays) {
            $days_html = "
            <div class='days-bar'>
              <div class='days-item'><div class='dval'>{$wdays}</div><div class='dlbl'>Working Days</div></div>
              <div class='days-item'><div class='dval'>{$ddays}</div><div class='dlbl'>Days Paid</div></div>
              <div class='days-item'><div class='dval'>{$absent_days}</div><div class='dlbl'>Leave / Absent</div></div>
              <div class='days-item'><div class='dval'>{$sym} {$daily_rate}</div><div class='dlbl'>Daily Rate</div></div>
            </div>";
        }

        // Earnings rows
        $earn_rows = "
          <tr>
            <td class='td-label'>Basic Salary</td>
            <td class='td-val'>{$sym} ".number_format($ps['basic_salary'],2)."</td>
          </tr>";
        foreach ($allowances as $a) {
            $n = htmlspecialchars($a['name'], ENT_QUOTES);
            $earn_rows .= "<tr><td class='td-label'>{$n}</td><td class='td-val'>{$sym} ".number_format($a['amount'],2)."</td></tr>";
        }

        // Deductions rows
        $ded_rows = '';
        foreach ($deductions as $d) {
            $n        = htmlspecialchars($d['name'], ENT_QUOTES);
            $is_stat  = (bool)preg_match('/\b(EPF|ETF|APIT|PAYE|tax)\b/i', $d['name']);
            $stat_tag = $is_stat ? " <span class='stat-tag'>Statutory</span>" : '';
            $ded_rows .= "<tr><td class='td-label' style='color:#ef4444'>{$n}{$stat_tag}</td><td class='td-val' style='color:#ef4444'>- {$sym} ".number_format($d['amount'],2)."</td></tr>";
        }
        if (!$ded_rows) {
            $ded_rows = "<tr><td colspan='2' class='td-label' style='color:#94a3b8'>No deductions for this period</td></tr>";
        }

        $gross_str = "{$sym} ".number_format($ps['gross_salary'],    2);
        $ded_str   = "{$sym} ".number_format($ps['total_deductions'],2);
        $net_str   = "{$sym} ".number_format($ps['net_salary'],      2);

        $footer_note = htmlspecialchars(
            $ps['footer_note'] ?? 'This is a computer-generated payslip and requires no signature.',
            ENT_QUOTES
        );

        $auth_line = $auth_by ?: 'Authorised Signatory';
        $auth_sub  = implode(' — ', array_filter([$auth_ttl, $cname]));

        header('Content-Type: text/html; charset=utf-8');
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Payslip — {$ename} — {$period}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#1e293b;background:#e2e8f0}

/* ── toolbar ── */
.toolbar{background:#1e293b;color:#fff;padding:12px 24px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.toolbar h3{font-size:14px;font-weight:600}
.toolbar-btns{display:flex;gap:10px}
.btn-print{background:#f97316;color:#fff;border:none;padding:9px 22px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:700}
.btn-print:hover{opacity:.88}
.btn-close{background:rgba(255,255,255,.15);color:#fff;border:none;padding:9px 16px;border-radius:6px;cursor:pointer;font-size:13px}

/* ── page ── */
.page{background:#fff;max-width:780px;margin:24px auto;border-radius:8px;box-shadow:0 4px 28px rgba(0,0,0,.18);overflow:hidden}

/* ── confidential stripe ── */
.confidential-bar{background:#f97316;color:#fff;font-size:9.5px;font-weight:800;letter-spacing:.18em;text-align:center;padding:4px;text-transform:uppercase}

/* ── header ── */
.hdr{background:#1e293b;padding:22px 28px;display:flex;justify-content:space-between;align-items:flex-start}
.hdr-left .co-name{font-size:19px;font-weight:800;color:#fff;margin-bottom:3px}
.co-sub{font-size:11px;color:rgba(255,255,255,.5);line-height:1.6;margin-top:2px}
.ps-lbl{font-size:23px;font-weight:900;color:#f97316;letter-spacing:2px;text-align:right}
.ps-meta{font-size:11.5px;color:rgba(255,255,255,.65);margin-top:6px;line-height:1.8;text-align:right}

/* ── ref bar ── */
.ref-bar{background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:7px 28px;display:flex;justify-content:space-between;font-size:11px;color:#64748b}

/* ── body ── */
.body{padding:22px 28px}

/* ── section title ── */
.sec-title{font-size:9.5px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#64748b;margin-bottom:9px;padding-bottom:5px;border-bottom:1.5px solid #e2e8f0}

/* ── info grid ── */
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-bottom:18px}
.info-block p{font-size:12.5px;color:#334155;line-height:1.7;margin:0}
.info-block strong{color:#1e293b;font-weight:700}

/* ── days bar ── */
.days-bar{display:grid;grid-template-columns:repeat(4,1fr);gap:0;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:18px;overflow:hidden}
.days-item{text-align:center;padding:10px 8px;border-right:1px solid #e2e8f0}
.days-item:last-child{border-right:none}
.dval{font-size:15px;font-weight:800;color:#1e293b}
.dlbl{font-size:9px;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-top:3px}

/* ── notes ── */
.notes-block{background:#fffbeb;border-left:3px solid #f59e0b;padding:9px 14px;font-size:12px;color:#92400e;margin-bottom:16px;border-radius:0 4px 4px 0}

/* ── salary grid ── */
.sal-grid{display:grid;grid-template-columns:1fr 1px 1fr;gap:0;margin-bottom:0}
.sal-divider{background:#e2e8f0}
.sal-col{padding:0 18px 0 0}
.sal-col:last-child{padding:0 0 0 18px}
.sal-col-title{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;margin-bottom:10px;padding-bottom:6px;border-bottom:2px solid #e2e8f0}
.sal-col-title.earn{color:#10b981}
.sal-col-title.ded{color:#ef4444}
.sal-col table{width:100%;border-collapse:collapse}
.td-label{font-size:12.5px;color:#334155;padding:5px 0;border-bottom:1px solid #f8fafc;vertical-align:middle}
.td-val{font-size:12.5px;text-align:right;padding:5px 0;border-bottom:1px solid #f8fafc;white-space:nowrap;font-weight:500}
.sal-total-row{display:flex;justify-content:space-between;padding:8px 0 0;font-weight:700;font-size:13px;border-top:2px solid #1e293b;margin-top:5px}
.stat-tag{font-size:8.5px;background:#fef3c7;color:#92400e;padding:1px 4px;border-radius:3px;margin-left:4px;font-weight:700;vertical-align:middle}

/* ── net bar ── */
.net-bar{background:#1e293b;padding:18px 28px;display:flex;justify-content:space-between;align-items:center}
.net-lbl{font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:rgba(255,255,255,.55)}
.net-period{font-size:11px;color:rgba(255,255,255,.4);margin-top:3px}
.net-amt{font-size:30px;font-weight:900;color:#f97316}

/* ── signature ── */
.sign-section{padding:18px 28px;display:grid;grid-template-columns:1fr 1fr;gap:24px;background:#f8fafc;border-top:1px solid #e2e8f0}
.sign-block{text-align:center}
.sign-line{border-top:1.5px solid #94a3b8;margin:36px 16px 7px;padding-top:7px;font-size:12px;font-weight:700;color:#475569}
.sign-sub{font-size:10.5px;color:#94a3b8;margin-top:2px}

/* ── footer ── */
.footer-note{padding:10px 28px;background:#f1f5f9;font-size:10.5px;color:#94a3b8;text-align:center;border-top:1px solid #e2e8f0}

/* ── print overrides ── */
@media print{
    @page{margin:8mm;size:A4}
    .toolbar{display:none!important}
    body{background:#fff}
    .page{box-shadow:none;border-radius:0;margin:0;max-width:100%}
    .confidential-bar{-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .hdr{-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .net-bar{-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .sign-section{-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .days-bar{-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .notes-block{-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .stat-tag{-webkit-print-color-adjust:exact;print-color-adjust:exact}
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

  <div class="confidential-bar">Confidential — For Employee Use Only</div>

  <div class="hdr">
    <div class="hdr-left">
      {$logo_html}
      <div class="co-name">{$cname}</div>
      {$co_sub}
    </div>
    <div>
      <div class="ps-lbl">PAYSLIP</div>
      <div class="ps-meta">
        Pay Period: <strong style="color:#fff">{$period}</strong><br>
        Pay Date: <strong style="color:#fff">{$pay_date_str}</strong><br>
        Issue Date: <strong style="color:#fff">{$issue_date}</strong><br>
        Status: <strong style="color:{$status_col}">{$ps['status']}</strong>
      </div>
    </div>
  </div>

  <div class="ref-bar">
    <span>Payslip Ref: <strong>{$ps_ref}</strong></span>
    <span>Issued On: {$issue_date}</span>
  </div>

  <div class="body">

    <div class="sec-title">Employee Information</div>
    <div class="info-grid">
      <div class="info-block">
        <p><strong>{$ename}</strong></p>
HTML;
        $desig = htmlspecialchars($ps['designation']??'', ENT_QUOTES);
        $dept  = htmlspecialchars($ps['department']??'',  ENT_QUOTES);
        $ei    = htmlspecialchars($ps['employee_id_no']??'', ENT_QUOTES);
        $em    = htmlspecialchars($ps['employee_email']??'', ENT_QUOTES);
        $ph    = htmlspecialchars($ps['employee_phone']??'', ENT_QUOTES);
        $bk    = htmlspecialchars($ps['bank_name']??'',    ENT_QUOTES);
        $ac    = htmlspecialchars($ps['account_no']??'',   ENT_QUOTES);

        if ($desig||$dept) echo "<p>".implode(' — ', array_filter([$desig,$dept]))."</p>";
        if ($ei)       echo "<p>Employee ID: <strong>{$ei}</strong></p>";
        if ($nic_no)   echo "<p>NIC No: <strong>{$nic_no}</strong></p>";
        if ($epf_mno)  echo "<p>EPF Member No: <strong>{$epf_mno}</strong></p>";
        if ($em)       echo "<p>{$em}</p>";
        if ($ph)       echo "<p>{$ph}</p>";
        echo "</div><div class='info-block'>";
        echo "<div class='sec-title'>Bank / Payment Details</div>";
        if ($bk) echo "<p>Bank: <strong>{$bk}</strong></p>";
        if ($ac) echo "<p>Account No: <strong>{$ac}</strong></p>";
        echo "<p style='margin-top:8px'>Basic Salary: <strong>{$sym} ".number_format($ps['basic_salary'],2)."</strong></p>";
        echo "<p>Gross Salary: <strong>{$sym} ".number_format($ps['gross_salary'],2)."</strong></p>";
        echo "<p>Total Deductions: <strong style='color:#ef4444'>- {$sym} ".number_format($ps['total_deductions'],2)."</strong></p>";
        echo "</div></div>";

        echo $days_html;
        echo $notes_block;

        echo <<<HTML
    <div class="sec-title">Salary Breakdown</div>
    <div class="sal-grid">
      <div class="sal-col">
        <div class="sal-col-title earn">Earnings</div>
        <table>
          {$earn_rows}
        </table>
        <div class="sal-total-row"><span>Gross Salary</span><span style="color:#10b981">{$gross_str}</span></div>
      </div>
      <div class="sal-divider"></div>
      <div class="sal-col">
        <div class="sal-col-title ded">Deductions</div>
        <table>
          {$ded_rows}
        </table>
        <div class="sal-total-row"><span>Total Deductions</span><span style="color:#ef4444">- {$ded_str}</span></div>
      </div>
    </div>

  </div>

  <div class="net-bar">
    <div>
      <div class="net-lbl">Net Salary Payable</div>
      <div class="net-period">{$period}</div>
    </div>
    <div class="net-amt">{$net_str}</div>
  </div>

  <div class="sign-section">
    <div class="sign-block">
      <div class="sign-line">Employee Signature &amp; Date</div>
      <div class="sign-sub">I acknowledge receipt of this payslip</div>
    </div>
    <div class="sign-block">
      <div class="sign-line">{$auth_line}</div>
      <div class="sign-sub">{$auth_sub}</div>
    </div>
  </div>

  <div class="footer-note">{$footer_note} &nbsp;|&nbsp; Ref: {$ps_ref}</div>

</div>
</body>
</html>
HTML;
        return;
    }

    // ──────────────────────────────────────────────────────────────────────
    // DOCX EXPORT
    // ──────────────────────────────────────────────────────────────────────
    if ($fmt === 'docx') {
        if (!class_exists('ZipArchive')) {
            http_response_code(500);
            die('ZipArchive not available. Please use Print/PDF instead.');
        }

        // XML-safe strings
        $xe = fn($s) => htmlspecialchars($s ?? '', ENT_XML1);

        $en    = $xe($ps['employee_name']);
        $pr    = $xe($ps['pay_period']);
        $cn    = $xe($ps['company_name'] ?? '');
        $crg   = $xe($ps['company_reg_no'] ?? '');
        $cph   = $xe($ps['company_phone'] ?? '');
        $cem   = $xe($ps['company_email'] ?? '');
        $ca    = $xe($ps['company_address'] ?? '');
        $epfeo = $xe($ps['epf_employer_no'] ?? '');
        $dg    = $xe($ps['designation'] ?? '');
        $dp    = $xe($ps['department'] ?? '');
        $ei    = $xe($ps['employee_id_no'] ?? '');
        $nicx  = $xe($ps['nic_number'] ?? '');
        $emfno = $xe($ps['epf_member_no'] ?? '');
        $em    = $xe($ps['employee_email'] ?? '');
        $ph    = $xe($ps['employee_phone'] ?? '');
        $bk    = $xe($ps['bank_name'] ?? '');
        $ac    = $xe($ps['account_no'] ?? '');
        $ft    = $xe($ps['footer_note'] ?? 'This is a computer-generated payslip and requires no signature.');
        $nt    = $xe($ps['notes'] ?? '');
        $st    = ucfirst($ps['status']);
        $refx  = $xe($ps['payslip_ref'] ?? ('PS-'.$pid));
        $authx = $xe($ps['authorized_by'] ?? 'Authorised Signatory');
        $attlx = $xe($ps['authorized_title'] ?? 'HR / Finance Department');
        $cur   = $ps['currency'] ?? 'LKR';

        $gross_s = "{$cur} ".number_format($ps['gross_salary'],    2);
        $ded_s   = "{$cur} ".number_format($ps['total_deductions'],2);
        $net_s   = "{$cur} ".number_format($ps['net_salary'],      2);
        $basic_s = "{$cur} ".number_format($ps['basic_salary'],    2);

        // Shared border XML
        $tbdr = '<w:tblBorders><w:top w:val="single" w:sz="4" w:color="E2E8F0"/><w:left w:val="single" w:sz="4" w:color="E2E8F0"/><w:bottom w:val="single" w:sz="4" w:color="E2E8F0"/><w:right w:val="single" w:sz="4" w:color="E2E8F0"/><w:insideH w:val="single" w:sz="4" w:color="E2E8F0"/><w:insideV w:val="single" w:sz="4" w:color="E2E8F0"/></w:tblBorders>';

        // Earnings XML
        $earn_xml = "<w:tr><w:tc><w:p><w:r><w:rPr><w:b/><w:color w:val='1E293B'/></w:rPr><w:t>Basic Salary</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:jc w:val='right'/></w:pPr><w:r><w:rPr><w:b/><w:color w:val='1E293B'/></w:rPr><w:t>{$basic_s}</w:t></w:r></w:p></w:tc></w:tr>";
        foreach ($allowances as $a) {
            $n = $xe($a['name']);
            $v = "{$cur} ".number_format($a['amount'],2);
            $earn_xml .= "<w:tr><w:tc><w:p><w:r><w:t>{$n}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:jc w:val='right'/></w:pPr><w:r><w:t>{$v}</w:t></w:r></w:p></w:tc></w:tr>";
        }

        // Deductions XML
        $ded_xml = '';
        foreach ($deductions as $d) {
            $n       = $xe($d['name']);
            $v       = "{$cur} ".number_format($d['amount'],2);
            $is_stat = (bool)preg_match('/\b(EPF|ETF|APIT|PAYE|tax)\b/i', $d['name']);
            $tag     = $is_stat ? " [Statutory]" : '';
            $ded_xml .= "<w:tr><w:tc><w:p><w:r><w:rPr><w:color w:val='EF4444'/></w:rPr><w:t>{$n}{$tag}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:jc w:val='right'/></w:pPr><w:r><w:rPr><w:color w:val='EF4444'/></w:rPr><w:t>- {$v}</w:t></w:r></w:p></w:tc></w:tr>";
        }
        if (!$ded_xml) $ded_xml = "<w:tr><w:tc><w:p><w:r><w:rPr><w:color w:val='94A3B8'/></w:rPr><w:t>No deductions for this period</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t></w:t></w:r></w:p></w:tc></w:tr>";

        // Days XML
        $days_xml = '';
        if ($wdays || $ddays) {
            $days_xml = "
<w:p><w:pPr><w:spacing w:before='160' w:after='60'/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val='22'/><w:color w:val='64748B'/></w:rPr><w:t>ATTENDANCE</w:t></w:r></w:p>
<w:tbl><w:tblPr><w:tblW w:type='pct' w:w='5000'/>{$tbdr}</w:tblPr>
<w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Working Days</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$wdays}</w:t></w:r></w:p></w:tc>
<w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Days Paid</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$ddays}</w:t></w:r></w:p></w:tc></w:tr>
<w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Leave / Absent</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$absent_days}</w:t></w:r></w:p></w:tc>
<w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Daily Rate</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$cur} {$daily_rate}</w:t></w:r></w:p></w:tc></w:tr>
</w:tbl>";
        }

        $notes_xml = $nt ? "<w:p><w:pPr><w:spacing w:before='120' w:after='60'/></w:pPr><w:r><w:rPr><w:i/><w:color w:val='92400E'/></w:rPr><w:t>Note: {$nt}</w:t></w:r></w:p>" : '';

        $doc_xml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
<w:body>

<!-- HEADER: CONFIDENTIAL + PAYSLIP label -->
<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="0"/></w:pPr>
  <w:r><w:rPr><w:b/><w:sz w:val="18"/><w:color w:val="F97316"/></w:rPr><w:t>CONFIDENTIAL — FOR EMPLOYEE USE ONLY</w:t></w:r></w:p>
<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="60" w:after="0"/></w:pPr>
  <w:r><w:rPr><w:b/><w:sz w:val="44"/><w:color w:val="F97316"/></w:rPr><w:t>PAYSLIP</w:t></w:r></w:p>

<!-- COMPANY INFO -->
<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="60" w:after="0"/></w:pPr>
  <w:r><w:rPr><w:b/><w:sz w:val="30"/><w:color w:val="1E293B"/></w:rPr><w:t>{$cn}</w:t></w:r></w:p>
<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="0"/></w:pPr>
  <w:r><w:rPr><w:sz w:val="20"/><w:color w:val="64748B"/></w:rPr><w:t>Reg: {$crg}  |  {$cph}  |  {$cem}</w:t></w:r></w:p>
<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="60"/></w:pPr>
  <w:r><w:rPr><w:sz w:val="18"/><w:color w:val="64748B"/></w:rPr><w:t>EPF Employer No: {$epfeo}</w:t></w:r></w:p>

<!-- PAY PERIOD META -->
<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="200"/></w:pPr>
  <w:r><w:rPr><w:sz w:val="22"/><w:color w:val="64748B"/></w:rPr><w:t>Pay Period: </w:t></w:r>
  <w:r><w:rPr><w:b/><w:sz w:val="22"/><w:color w:val="1E293B"/></w:rPr><w:t>{$pr}</w:t></w:r>
  <w:r><w:rPr><w:sz w:val="22"/><w:color w:val="64748B"/></w:rPr><w:t>  |  Pay Date: </w:t></w:r>
  <w:r><w:rPr><w:b/><w:sz w:val="22"/><w:color w:val="1E293B"/></w:rPr><w:t>{$pay_date_str}</w:t></w:r>
  <w:r><w:rPr><w:sz w:val="22"/><w:color w:val="64748B"/></w:rPr><w:t>  |  Status: </w:t></w:r>
  <w:r><w:rPr><w:b/><w:sz w:val="22"/><w:color w:val="10B981"/></w:rPr><w:t>{$st}</w:t></w:r>
  <w:r><w:rPr><w:sz w:val="20"/><w:color w:val="94A3B8"/></w:rPr><w:t>  |  Ref: {$refx}</w:t></w:r></w:p>

<!-- EMPLOYEE DETAILS -->
<w:p><w:pPr><w:spacing w:before="120" w:after="60"/></w:pPr>
  <w:r><w:rPr><w:b/><w:sz w:val="22"/><w:color w:val="64748B"/></w:rPr><w:t>EMPLOYEE DETAILS</w:t></w:r></w:p>
<w:tbl>
  <w:tblPr><w:tblW w:type="pct" w:w="5000"/>{$tbdr}</w:tblPr>
  <w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Employee Name</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/><w:color w:val="1E293B"/></w:rPr><w:t>{$en}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Employee ID</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$ei}</w:t></w:r></w:p></w:tc></w:tr>
  <w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Designation</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$dg}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Department</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$dp}</w:t></w:r></w:p></w:tc></w:tr>
  <w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>NIC Number</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/><w:color w:val="1E293B"/></w:rPr><w:t>{$nicx}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>EPF Member No</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$emfno}</w:t></w:r></w:p></w:tc></w:tr>
  <w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Email</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$em}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Phone</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$ph}</w:t></w:r></w:p></w:tc></w:tr>
  <w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Bank</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$bk}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Account No</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$ac}</w:t></w:r></w:p></w:tc></w:tr>
</w:tbl>

{$days_xml}
{$notes_xml}

<!-- EARNINGS -->
<w:p><w:pPr><w:spacing w:before="200" w:after="60"/></w:pPr>
  <w:r><w:rPr><w:b/><w:sz w:val="22"/><w:color w:val="10B981"/></w:rPr><w:t>EARNINGS</w:t></w:r></w:p>
<w:tbl>
  <w:tblPr><w:tblW w:type="pct" w:w="5000"/>{$tbdr}</w:tblPr>
  {$earn_xml}
  <w:tr>
    <w:tc><w:tcPr><w:shd w:fill="1E293B" w:val="clear"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/></w:rPr><w:t>Gross Salary</w:t></w:r></w:p></w:tc>
    <w:tc><w:tcPr><w:shd w:fill="1E293B" w:val="clear"/></w:tcPr><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="10B981"/></w:rPr><w:t>{$gross_s}</w:t></w:r></w:p></w:tc>
  </w:tr>
</w:tbl>

<!-- DEDUCTIONS -->
<w:p><w:pPr><w:spacing w:before="200" w:after="60"/></w:pPr>
  <w:r><w:rPr><w:b/><w:sz w:val="22"/><w:color w:val="EF4444"/></w:rPr><w:t>DEDUCTIONS (Statutory deductions marked [Statutory])</w:t></w:r></w:p>
<w:tbl>
  <w:tblPr><w:tblW w:type="pct" w:w="5000"/>{$tbdr}</w:tblPr>
  {$ded_xml}
  <w:tr>
    <w:tc><w:tcPr><w:shd w:fill="1E293B" w:val="clear"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/></w:rPr><w:t>Total Deductions</w:t></w:r></w:p></w:tc>
    <w:tc><w:tcPr><w:shd w:fill="1E293B" w:val="clear"/></w:tcPr><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="EF4444"/></w:rPr><w:t>- {$ded_s}</w:t></w:r></w:p></w:tc>
  </w:tr>
</w:tbl>

<!-- NET SALARY -->
<w:p><w:pPr><w:spacing w:before="260" w:after="60"/></w:pPr>
  <w:r><w:rPr><w:b/><w:sz w:val="36"/><w:color w:val="F97316"/></w:rPr><w:t>NET SALARY PAYABLE: {$net_s}</w:t></w:r></w:p>
<w:p><w:pPr><w:spacing w:before="0" w:after="0"/></w:pPr>
  <w:r><w:rPr><w:sz w:val="20"/><w:color w:val="64748B"/></w:rPr><w:t>Pay Period: {$pr}  |  Ref: {$refx}</w:t></w:r></w:p>

<!-- SIGNATURES -->
<w:p><w:pPr><w:spacing w:before="300" w:after="0"/></w:pPr>
  <w:r><w:rPr><w:b/><w:sz w:val="22"/><w:color w:val="64748B"/></w:rPr><w:t>ACKNOWLEDGEMENT</w:t></w:r></w:p>
<w:tbl>
  <w:tblPr><w:tblW w:type="pct" w:w="5000"/>{$tbdr}</w:tblPr>
  <w:tr>
    <w:tc><w:p><w:pPr><w:spacing w:before="600" w:after="60"/></w:pPr><w:r><w:rPr><w:sz w:val="20"/><w:color w:val="475569"/></w:rPr><w:t>Employee Signature &amp; Date</w:t></w:r></w:p><w:p><w:r><w:rPr><w:sz w:val="18"/><w:color w:val="94A3B8"/></w:rPr><w:t>I acknowledge receipt of this payslip</w:t></w:r></w:p></w:tc>
    <w:tc><w:p><w:pPr><w:spacing w:before="600" w:after="60"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="475569"/></w:rPr><w:t>{$authx}</w:t></w:r></w:p><w:p><w:r><w:rPr><w:sz w:val="18"/><w:color w:val="94A3B8"/></w:rPr><w:t>{$attlx}</w:t></w:r></w:p></w:tc>
  </w:tr>
</w:tbl>

<!-- FOOTER -->
<w:p><w:pPr><w:spacing w:before="300" w:after="0"/><w:jc w:val="center"/></w:pPr>
  <w:r><w:rPr><w:sz w:val="18"/><w:color w:val="94A3B8"/></w:rPr><w:t>{$ft}  |  Ref: {$refx}</w:t></w:r></w:p>

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