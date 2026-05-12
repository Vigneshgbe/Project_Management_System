<?php
/**
 * payslip_export.php
 * Industry-standard payslip: Print/PDF (A4) + DOCX
 * - CONFIDENTIAL bar is SCREEN ONLY (hidden on @media print)
 * - Digital signature image embedded from template
 * - All 12 legally required fields displayed
 * - Statutory deductions (EPF/ETF/APIT) labelled
 */

function exportPayslip(mysqli $db, int $pid, string $fmt): void {

    @$db->query("ALTER TABLE payslip_templates ADD COLUMN IF NOT EXISTS signature_image VARCHAR(500) DEFAULT NULL AFTER authorized_title");

    $ps = $db->query("
        SELECT p.*,
               t.company_name,    t.company_reg_no,   t.company_phone,
               t.company_email,   t.company_address,  t.company_logo,
               t.footer_note,     t.epf_employer_no,
               t.authorized_by,   t.authorized_title,  t.signature_image
        FROM payslips p
        LEFT JOIN payslip_templates t ON t.id = p.template_id
        WHERE p.id = $pid
    ")->fetch_assoc();

    if (!$ps) { http_response_code(404); die('Payslip not found.'); }

    $allowances = json_decode($ps['allowances'] ?? '[]', true) ?: [];
    $deductions = json_decode($ps['deductions']  ?? '[]', true) ?: [];

    $h = fn($s) => htmlspecialchars($s ?? '', ENT_QUOTES);

    $sym        = $h($ps['currency']         ?? 'LKR');
    $cname      = $h($ps['company_name']     ?? '');
    $creg       = $h($ps['company_reg_no']   ?? '');
    $cphone     = $h($ps['company_phone']    ?? '');
    $cemail     = $h($ps['company_email']    ?? '');
    $caddr      = $h($ps['company_address']  ?? '');
    $epf_eno    = $h($ps['epf_employer_no']  ?? '');
    $auth_by    = $h($ps['authorized_by']    ?? '');
    $auth_ttl   = $h($ps['authorized_title'] ?? 'HR / Finance Department');
    $ename      = $h($ps['employee_name']);
    $period     = $h($ps['pay_period']);
    $ps_ref     = $h($ps['payslip_ref']      ?? ('PS-'.$pid));
    $nic_no     = $h($ps['nic_number']       ?? '');
    $epf_mno    = $h($ps['epf_member_no']    ?? '');
    $desig      = $h($ps['designation']      ?? '');
    $dept       = $h($ps['department']       ?? '');
    $emp_id_no  = $h($ps['employee_id_no']   ?? '');
    $emp_email  = $h($ps['employee_email']   ?? '');
    $emp_phone  = $h($ps['employee_phone']   ?? '');
    $bank_name  = $h($ps['bank_name']        ?? '');
    $account_no = $h($ps['account_no']       ?? '');
    $notes_txt  = $h($ps['notes']            ?? '');

    $pay_date_str = $ps['pay_date'] ? date('d M Y', strtotime($ps['pay_date'])) : '—';
    $issue_date   = date('d M Y');
    $status_label = ucfirst($ps['status'] ?? 'draft');

    $wdays      = (int)($ps['working_days'] ?? 0);
    $ddays      = (int)($ps['days_paid']    ?? 0);
    $absent     = ($wdays && $ddays) ? max(0, $wdays - $ddays) : 0;
    $daily_rate = ($wdays > 0 && $ps['basic_salary'] > 0)
                  ? number_format($ps['basic_salary'] / $wdays, 2) : '';

    $basic_f = number_format($ps['basic_salary'],    2);
    $gross_f = number_format($ps['gross_salary'],    2);
    $ded_f   = number_format($ps['total_deductions'],2);
    $net_f   = number_format($ps['net_salary'],      2);

    // Logo base64
    $logo_html = '';
    if (!empty($ps['company_logo']) && file_exists($ps['company_logo'])) {
        $d = base64_encode(file_get_contents($ps['company_logo']));
        $m = mime_content_type($ps['company_logo']) ?: 'image/png';
        $logo_html = "<img src='data:{$m};base64,{$d}' class='co-logo' alt='Logo'>";
    }

    // Signature base64
    $sig_html = '';
    if (!empty($ps['signature_image']) && file_exists($ps['signature_image'])) {
        $sd = base64_encode(file_get_contents($ps['signature_image']));
        $sm = mime_content_type($ps['signature_image']) ?: 'image/png';
        $sig_html = "<img src='data:{$sm};base64,{$sd}' class='sig-img' alt='Signature'>";
    }

    // Earnings HTML
    $earn_html = "<tr><td>Basic Salary</td><td class='amt'>{$sym} {$basic_f}</td></tr>";
    foreach ($allowances as $a) {
        $n = $h($a['name']);
        $v = number_format($a['amount'], 2);
        $earn_html .= "<tr><td>{$n}</td><td class='amt'>{$sym} {$v}</td></tr>";
    }

    // Deductions HTML
    $ded_html = '';
    foreach ($deductions as $d) {
        $n       = $h($d['name']);
        $v       = number_format($d['amount'], 2);
        $is_stat = (bool)preg_match('/\b(EPF|ETF|APIT|PAYE|tax)\b/i', $d['name']);
        $badge   = $is_stat ? "<span class='stat-badge'>Statutory</span>" : '';
        $ded_html .= "<tr class='ded-row'><td>{$n} {$badge}</td><td class='amt'>- {$sym} {$v}</td></tr>";
    }
    if (!$ded_html) {
        $ded_html = "<tr><td class='muted' colspan='2'>No deductions for this period</td></tr>";
    }

    // Company sub-lines
    $co_parts = [];
    if ($creg)              $co_parts[] = "Reg. No: {$creg}";
    if ($caddr)             $co_parts[] = nl2br($caddr);
    if ($cphone && $cemail) $co_parts[] = "{$cphone} &nbsp;·&nbsp; {$cemail}";
    elseif ($cphone)        $co_parts[] = $cphone;
    elseif ($cemail)        $co_parts[] = $cemail;
    if ($epf_eno)           $co_parts[] = "EPF Employer Reg: {$epf_eno}";
    $co_sub = implode('<br>', $co_parts);

    // Days bar
    $days_html = '';
    if ($wdays || $ddays) {
        $dr = $daily_rate ?: '—';
        $days_html = "<div class='days-bar'>
          <div class='day-cell'><span class='dv'>{$wdays}</span><span class='dl'>Working Days</span></div>
          <div class='day-cell'><span class='dv'>{$ddays}</span><span class='dl'>Days Paid</span></div>
          <div class='day-cell'><span class='dv'>{$absent}</span><span class='dl'>Leave / Absent</span></div>
          <div class='day-cell'><span class='dv'>{$dr}</span><span class='dl'>Daily Rate ({$sym})</span></div>
        </div>";
    }

    $notes_html  = $notes_txt ? "<div class='notes-block'>&#128203; {$notes_txt}</div>" : '';
    $footer_note = $h($ps['footer_note'] ?? 'This is a computer-generated payslip and requires no signature.');
    $auth_display = $auth_by ?: 'Authorised Signatory';
    $auth_sub     = implode(' &nbsp;·&nbsp; ', array_filter([$auth_ttl, $cname]));

    // ══ PRINT / PDF ═══════════════════════════════════════════════════════
    if ($fmt === 'print') {
        header('Content-Type: text/html; charset=utf-8');
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Payslip — <?= $ename ?> — <?= $period ?></title>
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
html{font-size:13px}
body{font-family:Arial,Helvetica,sans-serif;color:#1e293b;background:#dde3ea}

/* ── Toolbar: screen only ── */
.toolbar{background:#1e293b;color:#fff;padding:11px 28px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.toolbar h3{font-size:13px;font-weight:600}
.btn-print{background:#f97316;color:#fff;border:none;padding:9px 24px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:700}
.btn-print:hover{background:#ea580c}
.btn-close{background:rgba(255,255,255,.15);color:#fff;border:none;padding:9px 16px;border-radius:6px;cursor:pointer;font-size:13px}
.tbr{display:flex;gap:10px}

/* ── Confidential: SCREEN ONLY — hidden when printing ── */
.conf-bar{background:#f97316;color:#fff;font-size:9px;font-weight:800;letter-spacing:.2em;text-align:center;padding:5px;text-transform:uppercase}

/* ── Page ── */
.page{background:#fff;max-width:800px;margin:20px auto;border-radius:6px;box-shadow:0 4px 32px rgba(0,0,0,.2);overflow:hidden}

/* ── Header ── */
.hdr{background:#1e293b;padding:22px 30px;display:flex;justify-content:space-between;align-items:flex-start;gap:20px}
.hdr-l{flex:1}
.co-logo{height:48px;margin-bottom:8px;display:block;border-radius:4px}
.co-name{font-size:18px;font-weight:800;color:#fff;margin-bottom:4px;line-height:1.2}
.co-sub{font-size:10.5px;color:rgba(255,255,255,.52);line-height:1.75;margin-top:2px}
.hdr-r{text-align:right;flex-shrink:0}
.ps-lbl{font-size:26px;font-weight:900;color:#f97316;letter-spacing:3px}
.ps-meta{font-size:11px;color:rgba(255,255,255,.65);line-height:1.9;margin-top:6px}
.ps-meta strong{color:#fff}
.s-issued{color:#34d399!important;font-weight:700}
.s-draft{color:#fbbf24!important;font-weight:700}

/* ── Ref bar ── */
.ref-bar{background:#f1f5f9;border-bottom:1.5px solid #e2e8f0;padding:7px 30px;display:flex;justify-content:space-between;font-size:11px;color:#64748b}
.ref-bar strong{color:#1e293b}

/* ── Body ── */
.body{padding:22px 30px}
.sec-title{font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;padding-bottom:6px;border-bottom:1.5px solid #e2e8f0;margin-bottom:12px}

/* ── Info grid ── */
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:18px}
.ib p{font-size:12px;color:#334155;line-height:1.75;margin:0}
.ib .en{font-size:14px;font-weight:800;color:#0f172a;margin-bottom:4px}
.frow{display:flex;gap:6px;align-items:baseline;margin-bottom:1px}
.flbl{font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;min-width:76px;flex-shrink:0}
.fval{font-size:12px;color:#0f172a;font-weight:600}
.fval.normal{font-weight:400;color:#475569}

/* ── Days bar ── */
.days-bar{display:grid;grid-template-columns:repeat(4,1fr);border:1.5px solid #e2e8f0;border-radius:6px;overflow:hidden;margin-bottom:18px;background:#f8fafc}
.day-cell{display:flex;flex-direction:column;align-items:center;padding:10px 6px;border-right:1px solid #e2e8f0}
.day-cell:last-child{border-right:none}
.dv{font-size:17px;font-weight:800;color:#1e293b}
.dl{font-size:9px;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-top:3px;text-align:center}

/* ── Notes ── */
.notes-block{background:#fffbeb;border-left:3px solid #f59e0b;padding:9px 14px;font-size:11.5px;color:#92400e;margin-bottom:18px;border-radius:0 4px 4px 0}

/* ── Salary section ── */
.sal-section{display:grid;grid-template-columns:1fr 1px 1fr;gap:0;margin-bottom:4px}
.sal-div{background:#e2e8f0}
.sal-col-l{padding-right:20px}
.sal-col-r{padding-left:20px}
.scol-title{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;padding-bottom:7px;border-bottom:2px solid #e2e8f0;margin-bottom:0}
.et{color:#059669}.dt{color:#dc2626}
.sal-tbl{width:100%;border-collapse:collapse}
.sal-tbl td{font-size:12px;color:#334155;padding:5px 0;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.sal-tbl .amt{text-align:right;white-space:nowrap;font-weight:500}
.sal-tbl .ded-row td{color:#dc2626}
.sal-tbl .muted{color:#94a3b8;font-style:italic}
.stat-badge{display:inline-block;font-size:8.5px;font-weight:700;background:#fef3c7;color:#92400e;padding:1px 5px;border-radius:3px;margin-left:4px;vertical-align:middle;border:1px solid #fde68a}
.sal-tbl tfoot td{font-size:12.5px;font-weight:700;padding:8px 0 2px;border-bottom:none;border-top:2px solid #1e293b}
.sal-tbl tfoot .amt{text-align:right}
.sal-tbl tfoot .ec{color:#059669}.sal-tbl tfoot .dc{color:#dc2626}

/* ── Summary strip ── */
.sum-strip{background:#f8fafc;border-top:1px solid #e2e8f0;padding:9px 30px;display:flex;justify-content:space-evenly}
.si{text-align:center;font-size:11px;color:#64748b}
.sv{font-size:13px;font-weight:700;color:#1e293b;display:block}
.sv.g{color:#059669}.sv.r{color:#dc2626}.sv.o{color:#f97316}

/* ── Net bar ── */
.net-bar{background:#1e293b;padding:18px 30px;display:flex;justify-content:space-between;align-items:center}
.nl .nlb{font-size:10px;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.5)}
.nl .np{font-size:11px;color:rgba(255,255,255,.4);margin-top:3px}
.na{font-size:32px;font-weight:900;color:#f97316;letter-spacing:.01em}

/* ── Sign section ── */
.sign-sec{padding:16px 30px 20px;background:#f8fafc;border-top:2px solid #e2e8f0}
.sign-sec-title{font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;margin-bottom:12px;padding-bottom:5px;border-bottom:1px solid #e2e8f0}
.sign-sec-grid{display:grid;grid-template-columns:1fr 1fr;gap:28px}
.sb{display:flex;flex-direction:column;align-items:flex-start}
.sig-img{height:52px;max-width:180px;object-fit:contain;display:block}
.sph{height:52px;display:block}
.sln{border-top:1.5px solid #94a3b8;margin-top:0;padding-top:6px;font-size:12px;font-weight:700;color:#334155;width:100%}
.ssub{font-size:10.5px;color:#64748b;margin-top:2px}
.ssub2{font-size:10px;color:#94a3b8;margin-top:1px}
.sign-decl{margin-top:13px;padding-top:9px;border-top:1px dashed #e2e8f0;font-size:10.5px;color:#94a3b8;line-height:1.7}

/* ── Footer ── */
.pf{padding:10px 30px;background:#f1f5f9;border-top:1px solid #e2e8f0;font-size:10px;color:#94a3b8;text-align:center;line-height:1.6}

/* ════════════════════════════
   PRINT RULES — A4 professional
   ════════════════════════════ */
@media print {
  @page{size:A4;margin:10mm 12mm}
  .toolbar          {display:none!important}
  .conf-bar         {display:none!important} /* SCREEN ONLY — not printed */
  body              {background:#fff}
  .page             {box-shadow:none;border-radius:0;margin:0;max-width:100%}
  .hdr,.net-bar,.days-bar,.ref-bar,.sum-strip,.sign-sec,.pf,.notes-block,.stat-badge
                    {-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .sal-section,.sign-sec,.net-bar{page-break-inside:avoid}
}
</style>
</head>
<body>

<div class="toolbar">
  <h3>Payslip &mdash; <?= $ename ?> &mdash; <?= $period ?></h3>
  <div class="tbr">
    <button class="btn-print" onclick="window.print()">&#128424;&nbsp; Print / Save as PDF</button>
    <button class="btn-close" onclick="window.close()">&#10005; Close</button>
  </div>
</div>

<!-- CONFIDENTIAL: visible on screen, hidden when printing -->
<div class="conf-bar">Confidential &mdash; For Employee Use Only</div>

<div class="page">

  <!-- ── Header ── -->
  <div class="hdr">
    <div class="hdr-l">
      <?= $logo_html ?>
      <div class="co-name"><?= $cname ?></div>
      <div class="co-sub"><?= $co_sub ?></div>
    </div>
    <div class="hdr-r">
      <div class="ps-lbl">PAYSLIP</div>
      <div class="ps-meta">
        Pay Period:&nbsp; <strong><?= $period ?></strong><br>
        Pay Date:&nbsp;&nbsp;&nbsp; <strong><?= $pay_date_str ?></strong><br>
        Issue Date:&nbsp;&nbsp; <strong><?= $issue_date ?></strong><br>
        Status:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong class="s-<?= $ps['status'] ?>"><?= $status_label ?></strong>
      </div>
    </div>
  </div>

  <!-- ── Ref bar ── -->
  <div class="ref-bar">
    <span>Payslip Ref: <strong><?= $ps_ref ?></strong></span>
    <span>Document issued: <?= $issue_date ?></span>
  </div>

  <!-- ── Body ── -->
  <div class="body">

    <div class="sec-title">Employee Information</div>
    <div class="info-grid">
      <div class="ib">
        <p class="en"><?= $ename ?></p>
        <?php if ($desig || $dept): ?>
        <p class="frow"><span class="flbl">Role</span><span class="fval"><?= implode(' &mdash; ', array_filter([$desig, $dept])) ?></span></p>
        <?php endif; ?>
        <?php if ($emp_id_no): ?><p class="frow"><span class="flbl">Employee ID</span><span class="fval"><?= $emp_id_no ?></span></p><?php endif; ?>
        <?php if ($nic_no): ?>   <p class="frow"><span class="flbl">NIC No</span><span class="fval"><?= $nic_no ?></span></p><?php endif; ?>
        <?php if ($epf_mno): ?>  <p class="frow"><span class="flbl">EPF Member</span><span class="fval"><?= $epf_mno ?></span></p><?php endif; ?>
        <?php if ($emp_email): ?><p class="frow"><span class="flbl">Email</span><span class="fval normal"><?= $emp_email ?></span></p><?php endif; ?>
        <?php if ($emp_phone): ?><p class="frow"><span class="flbl">Phone</span><span class="fval normal"><?= $emp_phone ?></span></p><?php endif; ?>
      </div>
      <div class="ib">
        <div class="sec-title">Bank &amp; Payment Details</div>
        <?php if ($bank_name): ?> <p class="frow"><span class="flbl">Bank</span><span class="fval"><?= $bank_name ?></span></p><?php endif; ?>
        <?php if ($account_no): ?><p class="frow"><span class="flbl">Account No</span><span class="fval"><?= $account_no ?></span></p><?php endif; ?>
        <p class="frow" style="margin-top:10px"><span class="flbl">Basic</span><span class="fval"><?= $sym ?> <?= $basic_f ?></span></p>
        <p class="frow"><span class="flbl">Gross</span><span class="fval"><?= $sym ?> <?= $gross_f ?></span></p>
        <p class="frow"><span class="flbl">Deductions</span><span class="fval" style="color:#dc2626">- <?= $sym ?> <?= $ded_f ?></span></p>
      </div>
    </div>

    <?= $days_html ?>
    <?= $notes_html ?>

    <div class="sec-title" style="margin-top:4px">Salary Breakdown</div>
    <div class="sal-section">
      <div class="sal-col-l">
        <div class="scol-title et">Earnings</div>
        <table class="sal-tbl">
          <tbody><?= $earn_html ?></tbody>
          <tfoot><tr><td class="ec">Gross Salary</td><td class="amt ec"><?= $sym ?> <?= $gross_f ?></td></tr></tfoot>
        </table>
      </div>
      <div class="sal-div"></div>
      <div class="sal-col-r">
        <div class="scol-title dt">Deductions</div>
        <table class="sal-tbl">
          <tbody><?= $ded_html ?></tbody>
          <tfoot><tr><td class="dc">Total Deductions</td><td class="amt dc">- <?= $sym ?> <?= $ded_f ?></td></tr></tfoot>
        </table>
      </div>
    </div>

  </div><!-- end body -->

  <!-- ── Summary strip ── -->
  <div class="sum-strip">
    <div class="si"><span class="sv g"><?= $sym ?> <?= $gross_f ?></span>Gross Salary</div>
    <div class="si"><span class="sv r">- <?= $sym ?> <?= $ded_f ?></span>Total Deductions</div>
    <div class="si"><span class="sv o"><?= $sym ?> <?= $net_f ?></span>Net Payable</div>
  </div>

  <!-- ── Net salary bar ── -->
  <div class="net-bar">
    <div class="nl">
      <div class="nlb">Net Salary Payable</div>
      <div class="np"><?= $period ?> &nbsp;·&nbsp; <?= $pay_date_str ?></div>
    </div>
    <div class="na"><?= $sym ?>&nbsp;<?= $net_f ?></div>
  </div>

  <!-- ── Authorisation Section (MNC-standard) ── -->
  <div class="sign-sec">
    <div class="sign-sec-title">Authorisation &amp; Certification</div>
    <div class="sign-sec-grid">

      <div class="sb">
        <div class="sph">&nbsp;</div>
        <div class="sln">Prepared by: Payroll / HR Department</div>
        <div class="ssub"><?= $cname ?></div>
        <div class="ssub2">This payslip is system-generated and certified</div>
      </div>

      <div class="sb">
        <?php if ($sig_html): ?>
        <div><?= $sig_html ?></div>
        <?php else: ?>
        <div class="sph">&nbsp;</div>
        <?php endif; ?>
        <div class="sln"><?= $auth_display ?></div>
        <div class="ssub"><?= $auth_sub ?></div>
        <div class="ssub2"><?= $creg ? 'Reg: '.$creg : '' ?></div>
      </div>

    </div>
    <div class="sign-decl">
      <strong style="color:#475569">Declaration:</strong>
      This payslip is a confidential document issued to the named employee for the pay period stated above.
      The figures represent the employee's earnings and statutory deductions as per applicable labour laws.
      For discrepancies, contact HR/Payroll within 7 working days of receipt.
      EPF/ETF contributions have been remitted to the Employees' Provident Fund and Employees' Trust Fund as required by law.
    </div>
  </div>

  <!-- ── Footer ── -->
  <div class="pf">
    <?= $footer_note ?> &nbsp;|&nbsp; Ref: <strong><?= $ps_ref ?></strong> &nbsp;|&nbsp; <?= $cname ?>
  </div>

</div><!-- end .page -->
</body>
</html>
<?php
        return;
    }

    // ══ DOCX EXPORT ═══════════════════════════════════════════════════════
    if ($fmt === 'docx') {
        if (!class_exists('ZipArchive')) {
            http_response_code(500); die('ZipArchive not available. Please use Print/PDF instead.');
        }

        $xe = fn($s) => htmlspecialchars($s ?? '', ENT_XML1);

        $en    = $xe($ps['employee_name']);
        $pr    = $xe($ps['pay_period']);
        $cn    = $xe($ps['company_name']     ?? '');
        $crg2  = $xe($ps['company_reg_no']   ?? '');
        $cph2  = $xe($ps['company_phone']    ?? '');
        $cem2  = $xe($ps['company_email']    ?? '');
        $epfeo = $xe($ps['epf_employer_no']  ?? '');
        $dg    = $xe($ps['designation']      ?? '');
        $dp    = $xe($ps['department']       ?? '');
        $ei    = $xe($ps['employee_id_no']   ?? '');
        $nicx  = $xe($ps['nic_number']       ?? '');
        $emfno = $xe($ps['epf_member_no']    ?? '');
        $emx   = $xe($ps['employee_email']   ?? '');
        $phx   = $xe($ps['employee_phone']   ?? '');
        $bk    = $xe($ps['bank_name']        ?? '');
        $ac    = $xe($ps['account_no']       ?? '');
        $ft    = $xe($ps['footer_note']      ?? 'This is a computer-generated payslip and requires no signature.');
        $nt    = $xe($ps['notes']            ?? '');
        $st    = ucfirst($ps['status']);
        $refx  = $xe($ps['payslip_ref']      ?? ('PS-'.$pid));
        $authx = $xe($ps['authorized_by']    ?? 'Authorised Signatory');
        $attlx = $xe($ps['authorized_title'] ?? 'HR / Finance Department');
        $cur   = $ps['currency'] ?? 'LKR';

        $tbdr = '<w:tblBorders><w:top w:val="single" w:sz="4" w:color="E2E8F0"/><w:left w:val="single" w:sz="4" w:color="E2E8F0"/><w:bottom w:val="single" w:sz="4" w:color="E2E8F0"/><w:right w:val="single" w:sz="4" w:color="E2E8F0"/><w:insideH w:val="single" w:sz="4" w:color="E2E8F0"/><w:insideV w:val="single" w:sz="4" w:color="E2E8F0"/></w:tblBorders>';

        $earn_xml = "<w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Basic Salary</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:jc w:val='right'/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>{$cur} {$basic_f}</w:t></w:r></w:p></w:tc></w:tr>";
        foreach ($allowances as $a) {
            $n = $xe($a['name']); $v = number_format($a['amount'], 2);
            $earn_xml .= "<w:tr><w:tc><w:p><w:r><w:t>{$n}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:jc w:val='right'/></w:pPr><w:r><w:t>{$cur} {$v}</w:t></w:r></w:p></w:tc></w:tr>";
        }

        $ded_xml = '';
        foreach ($deductions as $d) {
            $n = $xe($d['name']); $v = number_format($d['amount'], 2);
            $tag = preg_match('/\b(EPF|ETF|APIT|PAYE|tax)\b/i', $d['name']) ? ' [Statutory]' : '';
            $ded_xml .= "<w:tr><w:tc><w:p><w:r><w:rPr><w:color w:val='DC2626'/></w:rPr><w:t>{$n}{$tag}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:jc w:val='right'/></w:pPr><w:r><w:rPr><w:color w:val='DC2626'/></w:rPr><w:t>- {$cur} {$v}</w:t></w:r></w:p></w:tc></w:tr>";
        }
        if (!$ded_xml) $ded_xml = "<w:tr><w:tc><w:p><w:r><w:rPr><w:color w:val='94A3B8'/></w:rPr><w:t>No deductions for this period</w:t></w:r></w:p></w:tc><w:tc><w:p></w:p></w:tc></w:tr>";

        $days_xml = '';
        if ($wdays || $ddays) {
            $dr = $daily_rate ?: '—';
            $days_xml = "<w:p><w:pPr><w:spacing w:before='160' w:after='60'/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val='20'/><w:color w:val='64748B'/></w:rPr><w:t>ATTENDANCE</w:t></w:r></w:p>
<w:tbl><w:tblPr><w:tblW w:type='pct' w:w='5000'/>{$tbdr}</w:tblPr>
<w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Working Days</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$wdays}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Days Paid</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$ddays}</w:t></w:r></w:p></w:tc></w:tr>
<w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Leave / Absent</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$absent}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Daily Rate</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$cur} {$dr}</w:t></w:r></w:p></w:tc></w:tr>
</w:tbl>";
        }

        $notes_xml = $nt ? "<w:p><w:pPr><w:spacing w:before='100' w:after='80'/></w:pPr><w:r><w:rPr><w:i/><w:color w:val='92400E'/></w:rPr><w:t>Note: {$nt}</w:t></w:r></w:p>" : '';

        $doc_xml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
<w:body>
<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="0"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="44"/><w:color w:val="F97316"/></w:rPr><w:t>PAYSLIP</w:t></w:r></w:p>
<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="40" w:after="0"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="30"/><w:color w:val="1E293B"/></w:rPr><w:t>{$cn}</w:t></w:r></w:p>
<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="0"/></w:pPr><w:r><w:rPr><w:sz w:val="18"/><w:color w:val="64748B"/></w:rPr><w:t>Reg: {$crg2}  |  {$cph2}  |  {$cem2}  |  EPF Employer: {$epfeo}</w:t></w:r></w:p>
<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="160"/></w:pPr><w:r><w:rPr><w:sz w:val="20"/><w:color w:val="64748B"/></w:rPr><w:t>Pay Period: </w:t></w:r><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="1E293B"/></w:rPr><w:t>{$pr}</w:t></w:r><w:r><w:rPr><w:sz w:val="20"/><w:color w:val="64748B"/></w:rPr><w:t>  |  Pay Date: </w:t></w:r><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="1E293B"/></w:rPr><w:t>{$pay_date_str}</w:t></w:r><w:r><w:rPr><w:sz w:val="20"/><w:color w:val="64748B"/></w:rPr><w:t>  |  Status: </w:t></w:r><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="10B981"/></w:rPr><w:t>{$st}</w:t></w:r><w:r><w:rPr><w:sz w:val="18"/><w:color w:val="94A3B8"/></w:rPr><w:t>  |  Ref: {$refx}</w:t></w:r></w:p>
<w:p><w:pPr><w:spacing w:before="80" w:after="60"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="94A3B8"/></w:rPr><w:t>EMPLOYEE DETAILS</w:t></w:r></w:p>
<w:tbl><w:tblPr><w:tblW w:type="pct" w:w="5000"/>{$tbdr}</w:tblPr>
<w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Employee Name</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/><w:color w:val="0F172A"/></w:rPr><w:t>{$en}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Employee ID</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$ei}</w:t></w:r></w:p></w:tc></w:tr>
<w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Designation</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$dg}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Department</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$dp}</w:t></w:r></w:p></w:tc></w:tr>
<w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>NIC Number</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/><w:color w:val="0F172A"/></w:rPr><w:t>{$nicx}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>EPF Member No</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$emfno}</w:t></w:r></w:p></w:tc></w:tr>
<w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Email</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$emx}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Phone</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$phx}</w:t></w:r></w:p></w:tc></w:tr>
<w:tr><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Bank</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$bk}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Account No</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{$ac}</w:t></w:r></w:p></w:tc></w:tr>
</w:tbl>
{$days_xml}
{$notes_xml}
<w:p><w:pPr><w:spacing w:before="160" w:after="60"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="059669"/></w:rPr><w:t>EARNINGS</w:t></w:r></w:p>
<w:tbl><w:tblPr><w:tblW w:type="pct" w:w="5000"/>{$tbdr}</w:tblPr>
{$earn_xml}
<w:tr><w:tc><w:tcPr><w:shd w:fill="1E293B" w:val="clear"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/></w:rPr><w:t>Gross Salary</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:shd w:fill="1E293B" w:val="clear"/></w:tcPr><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="34D399"/></w:rPr><w:t>{$cur} {$gross_f}</w:t></w:r></w:p></w:tc></w:tr>
</w:tbl>
<w:p><w:pPr><w:spacing w:before="160" w:after="60"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="DC2626"/></w:rPr><w:t>DEDUCTIONS (items marked [Statutory] are legally mandated: EPF/ETF/APIT)</w:t></w:r></w:p>
<w:tbl><w:tblPr><w:tblW w:type="pct" w:w="5000"/>{$tbdr}</w:tblPr>
{$ded_xml}
<w:tr><w:tc><w:tcPr><w:shd w:fill="1E293B" w:val="clear"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/></w:rPr><w:t>Total Deductions</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:shd w:fill="1E293B" w:val="clear"/></w:tcPr><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="EF4444"/></w:rPr><w:t>- {$cur} {$ded_f}</w:t></w:r></w:p></w:tc></w:tr>
</w:tbl>
<w:p><w:pPr><w:spacing w:before="240" w:after="60"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="38"/><w:color w:val="F97316"/></w:rPr><w:t>NET SALARY PAYABLE: {$cur} {$net_f}</w:t></w:r></w:p>
<w:p><w:pPr><w:spacing w:before="0" w:after="0"/></w:pPr><w:r><w:rPr><w:sz w:val="18"/><w:color w:val="64748B"/></w:rPr><w:t>{$pr}  |  {$pay_date_str}  |  Ref: {$refx}</w:t></w:r></w:p>
<w:p><w:pPr><w:spacing w:before="280" w:after="60"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="64748B"/></w:rPr><w:t>ACKNOWLEDGEMENT</w:t></w:r></w:p>
<w:tbl><w:tblPr><w:tblW w:type="pct" w:w="5000"/>{$tbdr}</w:tblPr>
<w:tr><w:tc><w:p><w:pPr><w:spacing w:before="560" w:after="60"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="334155"/></w:rPr><w:t>Prepared by: Payroll / HR Department</w:t></w:r></w:p><w:p><w:r><w:rPr><w:sz w:val="18"/><w:color w:val="64748B"/></w:rPr><w:t>{$cn}</w:t></w:r></w:p><w:p><w:r><w:rPr><w:sz w:val="18"/><w:i/><w:color w:val="94A3B8"/></w:rPr><w:t>This payslip is system-generated and certified</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:spacing w:before="560" w:after="60"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="334155"/></w:rPr><w:t>{$authx}</w:t></w:r></w:p><w:p><w:r><w:rPr><w:sz w:val="18"/><w:color w:val="64748B"/></w:rPr><w:t>{$attlx}</w:t></w:r></w:p><w:p><w:r><w:rPr><w:sz w:val="18"/><w:color w:val="94A3B8"/></w:rPr><w:t>{$cn}</w:t></w:r></w:p></w:tc></w:tr>
</w:tbl>
<w:p><w:pPr><w:spacing w:before="160" w:after="60"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="18"/><w:color w:val="64748B"/></w:rPr><w:t>Declaration: </w:t></w:r><w:r><w:rPr><w:sz w:val="18"/><w:i/><w:color w:val="94A3B8"/></w:rPr><w:t>This payslip is a confidential document. The figures represent earnings and statutory deductions per applicable labour laws. EPF/ETF contributions have been remitted as required by law. For discrepancies contact HR/Payroll within 7 working days.</w:t></w:r></w:p>
<w:p><w:pPr><w:spacing w:before="240" w:after="0"/><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:sz w:val="18"/><w:color w:val="94A3B8"/></w:rPr><w:t>{$ft}  |  Ref: {$refx}</w:t></w:r></w:p>
<w:sectPr><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720"/></w:sectPr>
</w:body>
</w:document>
XML;

        $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $ps['employee_name']);
        $safe_per  = preg_replace('/\s+/', '_', $ps['pay_period']);
        $fname     = "Payslip_{$safe_name}_{$safe_per}.docx";

        $zip = new ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'ps_') . '.docx';
        if ($zip->open($tmp, ZipArchive::CREATE) !== true) { http_response_code(500); die('Cannot create DOCX.'); }
        $zip->addFromString('[Content_Types].xml','<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $zip->addFromString('word/_rels/document.xml.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>');
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