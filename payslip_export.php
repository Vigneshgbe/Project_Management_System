<?php
/**
 * payslip_export.php — Padak CRM Payslip Export
 * Single A4 page · MNC-accepted format · EPF/ETF statutory labels
 * Print/PDF + DOCX (ZipArchive) · Working signature image (base64 embedded)
 */
function exportPayslip(mysqli $db, int $pid, string $fmt): void {
    @$db->query("ALTER TABLE payslip_templates ADD COLUMN IF NOT EXISTS signature_image VARCHAR(500) DEFAULT NULL AFTER authorized_title");
    $ps=$db->query("SELECT p.*,t.company_name,t.company_reg_no,t.company_phone,t.company_email,t.company_address,t.company_logo,t.footer_note,t.epf_employer_no,t.authorized_by,t.authorized_title,t.signature_image FROM payslips p LEFT JOIN payslip_templates t ON t.id=p.template_id WHERE p.id=$pid")->fetch_assoc();
    if(!$ps){http_response_code(404);die('Payslip not found.');}
    $h=fn($s)=>htmlspecialchars((string)($s??''),ENT_QUOTES);
    $xv=fn($s)=>htmlspecialchars((string)($s??''),ENT_XML1|ENT_QUOTES,'UTF-8');
    $allow=json_decode($ps['allowances']??'[]',true)?:[];
    $deds=json_decode($ps['deductions']??'[]',true)?:[];
    $cur=$h($ps['currency']??'LKR');
    $bf=number_format((float)$ps['basic_salary'],2);
    $gf=number_format((float)$ps['gross_salary'],2);
    $df=number_format((float)$ps['total_deductions'],2);
    $nf=number_format((float)$ps['net_salary'],2);
    $cname=$h($ps['company_name']??'');$creg=$h($ps['company_reg_no']??'');
    $cphone=$h($ps['company_phone']??'');$cemail=$h($ps['company_email']??'');
    $caddr=$h($ps['company_address']??'');$epfno=$h($ps['epf_employer_no']??'');
    $authby=$h($ps['authorized_by']??'Authorised Signatory');
    $authtl=$h($ps['authorized_title']??'HR / Finance Department');
    $footer=$h($ps['footer_note']??'This is a computer-generated payslip.');
    $ename=$h($ps['employee_name']);$period=$h($ps['pay_period']);
    $psref=$h($ps['payslip_ref']??('PAY-'.$pid));
    $nic=$h($ps['nic_number']??'');$epfmno=$h($ps['epf_member_no']??'');
    $desig=$h($ps['designation']??'');$dept=$h($ps['department']??'');
    $empid=$h($ps['employee_id_no']??'');$email=$h($ps['employee_email']??'');
    $phone=$h($ps['employee_phone']??'');$bank=$h($ps['bank_name']??'');
    $accno=$h($ps['account_no']??'');$notes=$h($ps['notes']??'');
    $paydstr=$ps['pay_date']?date('d M Y',strtotime($ps['pay_date'])):'—';
    $issdate=date('d M Y');$stlbl=ucfirst($ps['status']??'draft');
    $wdays=(int)($ps['working_days']??0);$ddays=(int)($ps['days_paid']??0);
    $absent=($wdays&&$ddays)?max(0,$wdays-$ddays):0;
    $daily=($wdays>0&&(float)$ps['basic_salary']>0)?$cur.' '.number_format((float)$ps['basic_salary']/$wdays,2):'—';
    $logo_tag='';
    if(!empty($ps['company_logo'])&&file_exists($ps['company_logo'])){
        $d=base64_encode(file_get_contents($ps['company_logo']));
        $m=mime_content_type($ps['company_logo'])?:'image/png';
        $logo_tag="<img src='data:{$m};base64,{$d}' style='height:38px;display:block;margin-bottom:5px;border-radius:3px'>";}
    $sig_tag='';
    if(!empty($ps['signature_image'])&&file_exists($ps['signature_image'])){
        $d=base64_encode(file_get_contents($ps['signature_image']));
        $m=mime_content_type($ps['signature_image'])?:'image/png';
        $sig_tag="<img src='data:{$m};base64,{$d}' style='height:42px;max-width:150px;object-fit:contain;display:block;margin-bottom:3px'>";}
    $er="<tr><td>Basic Salary</td><td class='a'>{$cur} {$bf}</td></tr>";
    foreach($allow as $a)$er.="<tr><td>".$h($a['name'])."</td><td class='a'>{$cur} ".number_format((float)$a['amount'],2)."</td></tr>";
    $dr='';
    foreach($deds as $d){$st=preg_match('/\b(EPF|ETF|APIT|PAYE|Tax)\b/i',$d['name'])?"<span class='st'>Statutory</span>":'';$dr.="<tr class='dr'><td>".$h($d['name'])." {$st}</td><td class='a'>({$cur} ".number_format((float)$d['amount'],2).")</td></tr>";}
    if(!$dr)$dr="<tr><td colspan='2' style='color:#94a3b8;font-style:italic'>No deductions for this period</td></tr>";
    $co_contact=implode(' &nbsp;·&nbsp; ',array_filter([$cphone,$cemail]));
    $co_block=($creg?"Reg. No: {$creg}<br>":'').($caddr?nl2br($caddr).'<br>':'').($co_contact?$co_contact.'<br>':'').($epfno?"EPF Employer Reg: {$epfno}":'');
    $auth_sub2=implode(' &nbsp;|&nbsp; ',array_filter([$cname,$creg?'Reg: '.$creg:'']));

    if($fmt==='print'){header('Content-Type: text/html; charset=utf-8');?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Payslip <?="-{$ename}-{$period}"?></title><style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}html{font-size:10pt}
body{font-family:Arial,Helvetica,sans-serif;color:#1e293b;background:#dde3ea}
.tb{background:#1e293b;color:#fff;padding:9px 20px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:99}
.tb h3{font-size:12px;font-weight:600}.tbr{display:flex;gap:7px}
.bp{background:#f97316;color:#fff;border:none;padding:7px 18px;border-radius:4px;cursor:pointer;font-size:12px;font-weight:700}
.bc{background:rgba(255,255,255,.18);color:#fff;border:none;padding:7px 12px;border-radius:4px;cursor:pointer;font-size:12px}
.cf{background:#f97316;color:#fff;font-size:8px;font-weight:800;letter-spacing:.18em;text-align:center;padding:3px 0;text-transform:uppercase}
.pg{background:#fff;width:210mm;margin:12px auto;border-radius:3px;box-shadow:0 4px 28px rgba(0,0,0,.2);overflow:hidden;display:flex;flex-direction:column}
.hd{background:#1e293b;padding:14px 20px;display:flex;justify-content:space-between;align-items:flex-start;gap:14px}
.hl{flex:1}.co{font-size:13px;font-weight:800;color:#fff;margin-bottom:2px}.cm{font-size:8px;color:rgba(255,255,255,.5);line-height:1.6}
.hr2{text-align:right;flex-shrink:0}.pl{font-size:20px;font-weight:900;color:#f97316;letter-spacing:3px}
.pm{font-size:8.5px;color:rgba(255,255,255,.65);line-height:1.75;margin-top:4px}.pm strong{color:#fff}
.si{color:#34d399!important}.sd2{color:#fbbf24!important}
.rb{background:#f1f5f9;border-bottom:1px solid #e2e8f0;padding:3.5px 20px;display:flex;justify-content:space-between;font-size:8px;color:#64748b}
.bd{padding:10px 20px}
.st{font-size:7.5px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;padding-bottom:3px;border-bottom:1.5px solid #e2e8f0;margin-bottom:7px;margin-top:10px}
.st:first-child{margin-top:0}
table.et{width:100%;border-collapse:collapse;margin-bottom:8px}
table.et td{padding:3px 6px;border:1px solid #f1f5f9;font-size:8.5px}
table.et .el{background:#f8fafc;color:#475569;font-weight:700;width:17%;font-size:7.5px;white-space:nowrap}
table.et .ev{color:#1e293b;font-weight:500}
.db{display:grid;grid-template-columns:repeat(4,1fr);border:1px solid #e2e8f0;border-radius:3px;overflow:hidden;margin-bottom:8px}
.dc{text-align:center;padding:6px 3px;border-right:1px solid #e2e8f0;background:#f8fafc}.dc:last-child{border-right:none}
.dv{font-size:12px;font-weight:800;color:#1e293b;display:block}.dl{font-size:7px;color:#94a3b8;text-transform:uppercase;display:block;margin-top:1px}
.nb{background:#fffbeb;border-left:3px solid #f59e0b;padding:5px 8px;font-size:8px;color:#92400e;margin-bottom:8px}
.sw{display:grid;grid-template-columns:1fr 1px 1fr;margin-bottom:0}
.sdv{background:#e2e8f0}.sl{padding-right:12px}.sr{padding-left:12px}
.sh{font-size:7.5px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;padding-bottom:3px;border-bottom:2px solid #e2e8f0;margin-bottom:0}
.ec{color:#059669}.dc2{color:#dc2626}
table.slt{width:100%;border-collapse:collapse}
table.slt tr td{font-size:8.5px;color:#334155;padding:2.5px 0;border-bottom:1px solid #f1f5f9}
table.slt tr:last-child td{border-bottom:none}
.a{text-align:right;white-space:nowrap;font-weight:500}.dr td{color:#dc2626}
table.slt tfoot td{font-size:9px;font-weight:700;padding:5px 0 2px;border-top:2px solid #1e293b;border-bottom:none}
.st2{font-size:7px;background:#fef3c7;color:#92400e;padding:1px 3px;border-radius:2px;margin-left:2px;vertical-align:middle;border:1px solid #fde68a}
.ss{display:grid;grid-template-columns:repeat(3,1fr);background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;margin:8px -20px;padding:5px 20px}
.ssi{text-align:center}.ssv{font-size:9px;font-weight:800;display:block}.svg{color:#059669}.svr{color:#dc2626}.svo{color:#f97316}.ssl{font-size:7.5px;color:#64748b;display:block;margin-top:1px}
.nbar{background:#1e293b;padding:10px 20px;display:flex;justify-content:space-between;align-items:center}
.nlb{font-size:8px;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.07em}.np{font-size:8px;color:rgba(255,255,255,.4);margin-top:1px}
.na{font-size:19px;font-weight:900;color:#f97316}
.auth-bd{padding:9px 20px 10px}
.ag{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:8px}
.ac{display:flex;flex-direction:column}
.aw{height:44px;display:flex;align-items:flex-end;margin-bottom:0}
.al{border-top:1.5px solid #94a3b8;padding-top:4px;font-size:9px;font-weight:700;color:#334155}
.as_{font-size:8px;color:#64748b;margin-top:1px}.as2{font-size:7.5px;color:#94a3b8;margin-top:1px}
.decl{margin-top:8px;padding:5px 9px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:3px;font-size:7.5px;color:#64748b;line-height:1.6}
.decl strong{color:#475569}
.ft{background:#f1f5f9;border-top:1px solid #e2e8f0;padding:4px 20px;font-size:7.5px;color:#94a3b8;text-align:center;line-height:1.5}
@media print{
@page{size:A4;margin:7mm 8mm}
.tb,.cf{display:none!important}
body{background:#fff}
.pg{box-shadow:none;border-radius:0;margin:0;width:100%;-webkit-print-color-adjust:exact;print-color-adjust:exact}
.hd,.nbar,.db,.rb,.ss,.auth-bd,.ft,.nb,.st2,.decl{-webkit-print-color-adjust:exact;print-color-adjust:exact}
.sw,.ag,.nbar,.auth-bd{page-break-inside:avoid}
}</style></head><body>
<div class="tb"><h3>Payslip &mdash; <?=$ename?> &mdash; <?=$period?></h3><div class="tbr"><button class="bp" onclick="window.print()">&#128424; Print / Save as PDF</button><button class="bc" onclick="window.close()">&#10005; Close</button></div></div>
<div class="cf">Confidential &mdash; For Employee Use Only</div>
<div class="pg">
<div class="hd"><div class="hl"><?=$logo_tag?><div class="co"><?=$cname?:'Company'?></div><div class="cm"><?=$co_block?></div></div>
<div class="hr2"><div class="pl">PAYSLIP</div><div class="pm">Pay Period: &nbsp;<strong><?=$period?></strong><br>Pay Date: &nbsp;&nbsp;<strong><?=$paydstr?></strong><br>Issue Date: &nbsp;<strong><?=$issdate?></strong><br>Status: &nbsp;&nbsp;&nbsp;<strong class="<?=$ps['status']==='issued'?'si':'sd2'?>"><?=$stlbl?></strong></div></div></div>
<div class="rb"><span>Payslip Ref: <strong><?=$psref?></strong></span><span>Issued: <?=$issdate?></span></div>
<div class="bd">
<div class="st">Employee Information</div>
<table class="et"><tr><td class="el">Employee Name</td><td class="ev" style="font-weight:700;color:#0f172a"><?=$ename?></td><td class="el">Employee ID</td><td class="ev"><?=$empid?:'—'?></td></tr>
<tr><td class="el">Designation</td><td class="ev"><?=$desig?:'—'?></td><td class="el">Department</td><td class="ev"><?=$dept?:'—'?></td></tr>
<tr><td class="el">NIC Number</td><td class="ev" style="font-weight:700"><?=$nic?:'—'?></td><td class="el">EPF Member No</td><td class="ev"><?=$epfmno?:'—'?></td></tr>
<tr><td class="el">Email</td><td class="ev"><?=$email?:'—'?></td><td class="el">Phone</td><td class="ev"><?=$phone?:'—'?></td></tr>
<tr><td class="el">Bank</td><td class="ev"><?=$bank?:'—'?></td><td class="el">Account No</td><td class="ev" style="font-weight:700"><?=$accno?:'—'?></td></tr></table>
<?php if($wdays||$ddays):?><div class="st">Attendance</div>
<div class="db"><div class="dc"><span class="dv"><?=$wdays?:'—'?></span><span class="dl">Working Days</span></div><div class="dc"><span class="dv"><?=$ddays?:'—'?></span><span class="dl">Days Paid</span></div><div class="dc"><span class="dv"><?=$absent?></span><span class="dl">Leave/Absent</span></div><div class="dc"><span class="dv"><?=$daily?></span><span class="dl">Daily Rate</span></div></div><?php endif;?>
<?php if($notes):?><div class="nb">&#128203; <?=$notes?></div><?php endif;?>
<div class="st">Salary Breakdown</div>
<div class="sw"><div class="sl"><div class="sh ec">Earnings</div><table class="slt"><tbody><?=$er?></tbody><tfoot><tr><td class="ec">Gross Salary</td><td class="a ec"><?=$cur?> <?=$gf?></td></tr></tfoot></table></div>
<div class="sdv"></div>
<div class="sr"><div class="sh dc2">Deductions</div><table class="slt"><tbody><?=$dr?></tbody><tfoot><tr><td class="dc2">Total Deductions</td><td class="a dc2">(<?=$cur?> <?=$df?>)</td></tr></tfoot></table></div></div>
<div class="ss"><div class="ssi"><span class="ssv svg"><?=$cur?> <?=$gf?></span><span class="ssl">Gross Salary</span></div><div class="ssi"><span class="ssv svr">(<?=$cur?> <?=$df?>)</span><span class="ssl">Deductions</span></div><div class="ssi"><span class="ssv svo"><?=$cur?> <?=$nf?></span><span class="ssl">Net Payable</span></div></div>
</div>
<div class="nbar"><div><div class="nlb">Net Salary Payable</div><div class="np"><?=$period?> &middot; <?=$paydstr?></div></div><div class="na"><?=$cur?> <?=$nf?></div></div>
<div class="auth-bd"><div class="st" style="margin-top:0">Authorisation &amp; Certification</div>
<div class="ag">
<div class="ac"><div class="aw">&nbsp;</div><div class="al">Prepared by: Payroll / HR Department</div><div class="as_"><?=$cname?></div><div class="as2">This payslip is system-generated and certified accurate</div></div>
<div class="ac"><div class="aw"><?=$sig_tag?:'&nbsp;'?></div><div class="al"><?=$authby?></div><div class="as_"><?=$authtl?></div><div class="as2"><?=$auth_sub2?></div></div>
</div>
<div class="decl"><strong>Declaration:</strong> This is a confidential payslip issued to the named employee for the stated pay period. All figures represent verified earnings and statutory deductions per applicable labour laws. EPF (Employee 8%) and ETF (Employer 3%) contributions have been remitted to the Employees' Provident Fund and Trust Fund as required by law. APIT/PAYE tax withheld and remitted where applicable. For discrepancies contact HR/Payroll within <strong>7 working days</strong>.</div></div>
<div class="ft"><?=$footer?> &nbsp;|&nbsp; Ref: <strong><?=$psref?></strong> &nbsp;|&nbsp; <?=$cname?> &nbsp;|&nbsp; Issued: <?=$issdate?></div>
</div></body></html><?php return;}

    if($fmt==='docx'){
        if(!class_exists('ZipArchive')){http_response_code(500);die('ZipArchive not available.');}
        $xe=fn($s)=>htmlspecialchars((string)($s??''),ENT_XML1|ENT_QUOTES,'UTF-8');
        $tr=function(string $l,string $r,bool $b=false)use($xe):string{$bp=$b?'<w:b/>':'';return "<w:tr><w:tc><w:tcPr><w:shd w:val='clear' w:color='auto' w:fill='F8FAFC'/><w:tcMar><w:top w:w='60' w:type='dxa'/><w:left w:w='100' w:type='dxa'/><w:bottom w:w='60' w:type='dxa'/><w:right w:w='80' w:type='dxa'/></w:tcMar></w:tcPr><w:p><w:r><w:rPr><w:sz w:val='18'/><w:color w:val='475569'/></w:rPr><w:t xml:space='preserve'>{$l}</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcMar><w:top w:w='60' w:type='dxa'/><w:left w:w='80' w:type='dxa'/><w:bottom w:w='60' w:type='dxa'/><w:right w:w='100' w:type='dxa'/></w:tcMar></w:tcPr><w:p><w:r><w:rPr>{$bp}<w:sz w:val='18'/><w:color w:val='0F172A'/></w:rPr><w:t xml:space='preserve'>{$r}</w:t></w:r></w:p></w:tc></w:tr>";};
        $sh=fn(string $t)=>"<w:p><w:pPr><w:spacing w:before='180' w:after='50'/><w:pBdr><w:bottom w:val='single' w:sz='4' w:space='1' w:color='E2E8F0'/></w:pBdr></w:pPr><w:r><w:rPr><w:b/><w:sz w:val='16'/><w:caps/><w:color w:val='94A3B8'/></w:rPr><w:t>{$t}</w:t></w:r></w:p>";
        $xen=$xe($ps['employee_name']);$xeper=$xe($ps['pay_period']);$xecn=$xe($ps['company_name']??'');
        $xecrg=$xe($ps['company_reg_no']??'');$xeepfe=$xe($ps['epf_employer_no']??'');
        $xeauth=$xe($ps['authorized_by']??'Authorised Signatory');$xeatt=$xe($ps['authorized_title']??'HR / Finance Department');
        $xeref=$xe($ps['payslip_ref']??('PAY-'.$pid));$xeft=$xe($ps['footer_note']??'Computer-generated payslip.');
        $xest=ucfirst($ps['status']??'draft');$xecur=$xe($ps['currency']??'LKR');
        $xent=$xe($ps['notes']??'');
        $er2=$tr('Basic Salary',$xecur.' '.$xe($bf),true);
        foreach($allow as $a)$er2.=$tr($xe($a['name']),$xecur.' '.$xe(number_format((float)$a['amount'],2)));
        $dr2='';foreach($deds as $d){$tag=preg_match('/\b(EPF|ETF|APIT|PAYE|Tax)\b/i',$d['name'])?'[Statutory]':'';$dr2.=$tr($xe($d['name']).($tag?" {$tag}":''),'('.$xecur.' '.$xe(number_format((float)$d['amount'],2)).')');}
        if(!$dr2)$dr2=$tr('No deductions for this period','—');
        $days2='';if($wdays||$ddays){$days2=$sh('Attendance')."<w:tbl><w:tblPr><w:tblW w:w='0' w:type='auto'/></w:tblPr>".$tr('Working Days',(string)$wdays).$tr('Days Paid',(string)$ddays).$tr('Leave/Absent',(string)$absent).$tr('Daily Rate',$xe($daily))."</w:tbl>";}
        $notes2=$xent?"<w:p><w:r><w:rPr><w:i/><w:sz w:val='18'/><w:color w:val='92400E'/></w:rPr><w:t xml:space='preserve'>Note: {$xent}</w:t></w:r></w:p>":'';
        $emp_rows=$tr('Employee Name',$xen,true).$tr('Employee ID',$xe($ps['employee_id_no']??'—')).$tr('Designation',$xe($ps['designation']??'—')).$tr('Department',$xe($ps['department']??'—')).$tr('NIC Number',$xe($ps['nic_number']??'—'),true).$tr('EPF Member No',$xe($ps['epf_member_no']??'—')).$tr('Email',$xe($ps['employee_email']??'—')).$tr('Phone',$xe($ps['employee_phone']??'—')).$tr('Bank',$xe($ps['bank_name']??'—')).$tr('Account No',$xe($ps['account_no']??'—'),true);
        $tbdr='<w:tblBorders><w:top w:val="single" w:sz="4" w:color="E2E8F0"/><w:left w:val="single" w:sz="4" w:color="E2E8F0"/><w:bottom w:val="single" w:sz="4" w:color="E2E8F0"/><w:right w:val="single" w:sz="4" w:color="E2E8F0"/><w:insideH w:val="single" w:sz="4" w:color="E2E8F0"/><w:insideV w:val="single" w:sz="4" w:color="E2E8F0"/></w:tblBorders>';
        $doc='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .'<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="40"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="48"/><w:color w:val="F97316"/></w:rPr><w:t>PAYSLIP</w:t></w:r></w:p>'
            .'<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="30"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="30"/><w:color w:val="1E293B"/></w:rPr><w:t>'.$xecn.'</w:t></w:r></w:p>'
            .'<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="160"/></w:pPr><w:r><w:rPr><w:sz w:val="18"/><w:color w:val="64748B"/></w:rPr><w:t xml:space="preserve">Reg: '.$xecrg.'  |  EPF: '.$xeepfe.'  |  Pay Period: </w:t></w:r><w:r><w:rPr><w:b/><w:sz w:val="18"/><w:color w:val="1E293B"/></w:rPr><w:t>'.$xeper.'</w:t></w:r><w:r><w:rPr><w:sz w:val="18"/><w:color w:val="64748B"/></w:rPr><w:t xml:space="preserve">  |  Date: </w:t></w:r><w:r><w:rPr><w:b/><w:sz w:val="18"/><w:color w:val="1E293B"/></w:rPr><w:t>'.$paydstr.'</w:t></w:r><w:r><w:rPr><w:sz w:val="18"/><w:color w:val="64748B"/></w:rPr><w:t xml:space="preserve">  |  Status: '.$xest.'  |  Ref: '.$xeref.'</w:t></w:r></w:p>'
            .$sh('Employee Information')
            .'<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/>'.$tbdr.'</w:tblPr>'.$emp_rows.'</w:tbl>'
            .$days2.$notes2
            .$sh('Earnings').'<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/>'.$tbdr.'</w:tblPr>'.$er2
            .'<w:tr><w:tc><w:tcPr><w:shd w:val="clear" w:color="auto" w:fill="1E293B"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="FFFFFF"/></w:rPr><w:t>Gross Salary</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:shd w:val="clear" w:color="auto" w:fill="1E293B"/></w:tcPr><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="34D399"/></w:rPr><w:t>'.$xecur.' '.$xe($gf).'</w:t></w:r></w:p></w:tc></w:tr></w:tbl>'
            .$sh('Deductions — items marked [Statutory] are legally mandated').'<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/>'.$tbdr.'</w:tblPr>'.$dr2
            .'<w:tr><w:tc><w:tcPr><w:shd w:val="clear" w:color="auto" w:fill="1E293B"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="FFFFFF"/></w:rPr><w:t>Total Deductions</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:shd w:val="clear" w:color="auto" w:fill="1E293B"/></w:tcPr><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="EF4444"/></w:rPr><w:t>('.$xecur.' '.$xe($df).')</w:t></w:r></w:p></w:tc></w:tr></w:tbl>'
            .'<w:p><w:pPr><w:spacing w:before="200" w:after="60"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="36"/><w:color w:val="F97316"/></w:rPr><w:t xml:space="preserve">NET SALARY PAYABLE: '.$xecur.' '.$xe($nf).'</w:t></w:r></w:p>'
            .'<w:p><w:pPr><w:spacing w:before="0" w:after="0"/></w:pPr><w:r><w:rPr><w:sz w:val="18"/><w:color w:val="64748B"/></w:rPr><w:t xml:space="preserve">'.$xeper.'  |  '.$paydstr.'  |  Ref: '.$xeref.'</w:t></w:r></w:p>'
            .$sh('Authorisation &amp; Certification')
            .'<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/></w:tblPr><w:tr><w:tc><w:p><w:pPr><w:spacing w:before="360" w:after="60"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="334155"/></w:rPr><w:t>Prepared by: Payroll / HR Department</w:t></w:r></w:p><w:p><w:r><w:rPr><w:sz w:val="18"/><w:color w:val="64748B"/></w:rPr><w:t>'.$xecn.'</w:t></w:r></w:p><w:p><w:r><w:rPr><w:i/><w:sz w:val="17"/><w:color w:val="94A3B8"/></w:rPr><w:t>System-generated and certified accurate</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:spacing w:before="360" w:after="60"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="334155"/></w:rPr><w:t>'.$xeauth.'</w:t></w:r></w:p><w:p><w:r><w:rPr><w:sz w:val="18"/><w:color w:val="64748B"/></w:rPr><w:t>'.$xeatt.'</w:t></w:r></w:p><w:p><w:r><w:rPr><w:sz w:val="17"/><w:color w:val="94A3B8"/></w:rPr><w:t xml:space="preserve">'.$xecn.'  Reg: '.$xecrg.'</w:t></w:r></w:p></w:tc></w:tr></w:tbl>'
            .'<w:p><w:pPr><w:spacing w:before="120" w:after="60"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="17"/><w:color w:val="475569"/></w:rPr><w:t xml:space="preserve">Declaration: </w:t></w:r><w:r><w:rPr><w:i/><w:sz w:val="17"/><w:color w:val="64748B"/></w:rPr><w:t xml:space="preserve">Confidential payslip. EPF (8%) and ETF (3%) remitted per law. APIT/PAYE withheld where applicable. Discrepancies: contact HR/Payroll within 7 working days.</w:t></w:r></w:p>'
            .'<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="140" w:after="0"/></w:pPr><w:r><w:rPr><w:sz w:val="16"/><w:color w:val="94A3B8"/></w:rPr><w:t xml:space="preserve">'.$xeft.'  |  Ref: '.$xeref.'  |  '.$xecn.'</w:t></w:r></w:p>'
            .'<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720"/></w:sectPr></w:body></w:document>';
        $safe=preg_replace('/[^a-zA-Z0-9_-]/','_',$ps['employee_name']??'Emp');
        $safep=preg_replace('/\s+/','_',$ps['pay_period']??'Period');
        $fname="Payslip_{$safe}_{$safep}.docx";
        $tmp=tempnam(sys_get_temp_dir(),'ps_').'.docx';
        $zip=new ZipArchive();$zip->open($tmp,ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml','<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $zip->addFromString('word/_rels/document.xml.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>');
        $zip->addFromString('word/document.xml',$doc);$zip->close();
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="'.rawurlencode($fname).'"');
        header('Content-Length: '.filesize($tmp));header('Cache-Control: no-store, no-cache');
        readfile($tmp);@unlink($tmp);
    }
}