<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
requireRole(['admin','manager']);
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

// ── POST HANDLERS ──
ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── SAVE TEMPLATE ──
    if ($action === 'save_template') {
        $name  = trim($_POST['tpl_name'] ?? '');
        $cname = trim($_POST['company_name'] ?? '');
        $caddr = trim($_POST['company_address'] ?? '');
        $foot  = trim($_POST['footer_note'] ?? '');
        $def   = (int)($_POST['is_default'] ?? 0);
        $tid   = (int)($_POST['template_id'] ?? 0);
        if ($name && $cname) {
            // Handle logo upload
            $logo = null;
            if (!empty($_FILES['company_logo']['tmp_name']) && $_FILES['company_logo']['error'] === 0) {
                $ext = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['png','jpg','jpeg'])) {
                    if (!is_dir(UPLOAD_DOC_DIR)) mkdir(UPLOAD_DOC_DIR, 0755, true);
                    $fn = 'logo_'.uniqid().'.'.$ext;
                    if (move_uploaded_file($_FILES['company_logo']['tmp_name'], UPLOAD_DOC_DIR.$fn)) {
                        $logo = 'uploads/documents/'.$fn;
                    }
                }
            }
            if ($def) $db->query("UPDATE payslip_templates SET is_default=0");
            if ($tid) {
                if ($logo) {
                    $stmt = $db->prepare("UPDATE payslip_templates SET name=?,company_name=?,company_address=?,company_logo=?,footer_note=?,is_default=? WHERE id=$tid");
                    $stmt->bind_param("sssssi",$name,$cname,$caddr,$logo,$foot,$def);
                } else {
                    $stmt = $db->prepare("UPDATE payslip_templates SET name=?,company_name=?,company_address=?,footer_note=?,is_default=? WHERE id=$tid");
                    $stmt->bind_param("ssssi",$name,$cname,$caddr,$foot,$def);
                }
            } else {
                if ($logo) {
                    $stmt = $db->prepare("INSERT INTO payslip_templates (name,company_name,company_address,company_logo,footer_note,is_default,created_by) VALUES (?,?,?,?,?,?,?)");
                    $stmt->bind_param("sssssii",$name,$cname,$caddr,$logo,$foot,$def,$uid);
                } else {
                    $stmt = $db->prepare("INSERT INTO payslip_templates (name,company_name,company_address,footer_note,is_default,created_by) VALUES (?,?,?,?,?,?)");
                    $stmt->bind_param("ssssii",$name,$cname,$caddr,$foot,$def,$uid);
                }
            }
            $stmt->execute();
            flash('Template saved.','success');
        }
        ob_end_clean(); header('Location: payslip.php?section=templates'); exit;
    }

    // ── DELETE TEMPLATE ──
    if ($action === 'delete_template' && isAdmin()) {
        $tid = (int)($_POST['template_id'] ?? 0);
        $db->query("DELETE FROM payslip_templates WHERE id=$tid");
        flash('Template deleted.','success');
        ob_end_clean(); header('Location: payslip.php?section=templates'); exit;
    }

    // ── SAVE PAYSLIP ──
    if ($action === 'save_payslip') {
        $emp_name  = trim($_POST['employee_name'] ?? '');
        $emp_email = trim($_POST['employee_email'] ?? '');
        $emp_phone = trim($_POST['employee_phone'] ?? '');
        $desig     = trim($_POST['designation'] ?? '');
        $dept      = trim($_POST['department'] ?? '');
        $emp_no    = trim($_POST['employee_id_no'] ?? '');
        $period    = trim($_POST['pay_period'] ?? '');
        $pay_date  = $_POST['pay_date'] ?: null;
        $basic     = (float)($_POST['basic_salary'] ?? 0);
        $currency  = $_POST['currency'] ?? 'LKR';
        $bank_name = trim($_POST['bank_name'] ?? '');
        $acct_no   = trim($_POST['account_no'] ?? '');
        $notes     = trim($_POST['notes'] ?? '');
        $tpl_id    = (int)($_POST['template_id'] ?? 0) ?: null;
        $emp_id    = (int)($_POST['employee_id'] ?? 0) ?: null;
        $status    = $_POST['ps_status'] ?? 'draft';

        $allowances = $deductions = [];
        foreach ($_POST['allowance_name'] ?? [] as $i => $n) {
            $amt = (float)($_POST['allowance_amount'][$i] ?? 0);
            if (trim($n) && $amt > 0) $allowances[] = ['name'=>trim($n),'amount'=>$amt];
        }
        foreach ($_POST['deduction_name'] ?? [] as $i => $n) {
            $amt = (float)($_POST['deduction_amount'][$i] ?? 0);
            if (trim($n) && $amt > 0) $deductions[] = ['name'=>trim($n),'amount'=>$amt];
        }
        $total_allow = array_sum(array_column($allowances,'amount'));
        $total_ded   = array_sum(array_column($deductions,'amount'));
        $gross       = $basic + $total_allow;
        $net         = $gross - $total_ded;
        $allow_json  = json_encode($allowances);
        $ded_json    = json_encode($deductions);

        $pid = (int)($_POST['payslip_id'] ?? 0);
        if ($pid) {
            $stmt = $db->prepare("UPDATE payslips SET template_id=?,employee_id=?,employee_name=?,employee_email=?,employee_phone=?,designation=?,department=?,employee_id_no=?,pay_period=?,pay_date=?,basic_salary=?,allowances=?,deductions=?,gross_salary=?,total_deductions=?,net_salary=?,currency=?,bank_name=?,account_no=?,notes=?,status=? WHERE id=$pid");
            $stmt->bind_param("iissssssssdssdddsssss",$tpl_id,$emp_id,$emp_name,$emp_email,$emp_phone,$desig,$dept,$emp_no,$period,$pay_date,$basic,$allow_json,$ded_json,$gross,$total_ded,$net,$currency,$bank_name,$acct_no,$notes,$status);
        } else {
            $stmt = $db->prepare("INSERT INTO payslips (template_id,employee_id,employee_name,employee_email,employee_phone,designation,department,employee_id_no,pay_period,pay_date,basic_salary,allowances,deductions,gross_salary,total_deductions,net_salary,currency,bank_name,account_no,notes,status,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("iissssssssdssdddsssssi",$tpl_id,$emp_id,$emp_name,$emp_email,$emp_phone,$desig,$dept,$emp_no,$period,$pay_date,$basic,$allow_json,$ded_json,$gross,$total_ded,$net,$currency,$bank_name,$acct_no,$notes,$status,$uid);
        }
        $stmt->execute();
        $new_pid = $pid ?: (int)$db->insert_id;
        logActivity('generated payslip', $emp_name, $new_pid);
        flash('Payslip saved successfully.','success');
        ob_end_clean(); header("Location: payslip.php?view=$new_pid"); exit;
    }

    // ── DELETE PAYSLIP ──
    if ($action === 'delete_payslip' && isAdmin()) {
        $pid = (int)($_POST['payslip_id'] ?? 0);
        $db->query("DELETE FROM payslips WHERE id=$pid");
        flash('Payslip deleted.','success');
        ob_end_clean(); header('Location: payslip.php'); exit;
    }
}
ob_end_clean();

// ── EXPORT ──
if (isset($_GET['export'])) {
    $pid = (int)($_GET['pid'] ?? 0);
    $fmt = $_GET['export'];
    if ($pid && in_array($fmt,['print','docx'])) {
        require_once 'payslip_export.php';
        exportPayslip($db, $pid, $fmt);
        exit;
    }
}

// ── DATA ──
$section  = $_GET['section'] ?? 'list';
$view_id  = (int)($_GET['view'] ?? 0);
$edit_id  = (int)($_GET['edit'] ?? 0);
$edit_tpl = (int)($_GET['edit_tpl'] ?? 0);

if ($section === 'create' || $edit_id) $section = 'create';
if ($section === 'templates')          $section = 'templates';

$payslips = $db->query("
    SELECT p.*, t.company_name
    FROM payslips p
    LEFT JOIN payslip_templates t ON t.id=p.template_id
    ORDER BY p.created_at DESC LIMIT 100
")->fetch_all(MYSQLI_ASSOC);

$templates = $db->query("SELECT * FROM payslip_templates ORDER BY is_default DESC, name ASC")->fetch_all(MYSQLI_ASSOC);
$default_tpl = null;
foreach ($templates as $t) { if ($t['is_default']) { $default_tpl=$t; break; } }
if (!$default_tpl && $templates) $default_tpl = $templates[0];

$single = null;
if ($view_id) {
    $single = $db->query("SELECT p.*, t.company_name,t.company_address,t.company_logo,t.footer_note
        FROM payslips p LEFT JOIN payslip_templates t ON t.id=p.template_id
        WHERE p.id=$view_id")->fetch_assoc();
}

$ep = null;
if ($edit_id) {
    $ep = $db->query("SELECT * FROM payslips WHERE id=$edit_id")->fetch_assoc();
}
$et = $edit_tpl ? $db->query("SELECT * FROM payslip_templates WHERE id=$edit_tpl")->fetch_assoc() : null;

$employees = $db->query("SELECT id,name,email,phone,department FROM users WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$ep_allow = $ep ? (json_decode($ep['allowances']??'[]',true)?:[]) : [['name'=>'House Allowance','amount'=>0],['name'=>'Transport Allowance','amount'=>0]];
$ep_ded   = $ep ? (json_decode($ep['deductions']??'[]',true)?:[])  : [['name'=>'EPF (8%)','amount'=>0],['name'=>'ETF (3%)','amount'=>0]];

renderLayout('Payslip Generator','payslip');
?>
<style>
/* ── TABS ── */
.ps-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:22px}
.ps-tab{padding:10px 20px;font-size:13px;font-weight:600;color:var(--text3);cursor:pointer;border:none;background:none;border-bottom:3px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s;display:flex;align-items:center;gap:6px}
.ps-tab:hover{color:var(--text2)}
.ps-tab.active{color:var(--orange);border-bottom-color:var(--orange)}

/* ── LAYOUT ── */
.ps-split{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.ps-panel{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
.ps-panel-head{padding:13px 16px;border-bottom:1px solid var(--border);background:var(--bg3);display:flex;align-items:center;justify-content:space-between}
.ps-panel-title{font-size:13px;font-weight:700;font-family:var(--font-display);display:flex;align-items:center;gap:7px}
.ps-panel-body{padding:16px}

/* ── LIST ── */
.ps-row{display:flex;align-items:center;gap:14px;padding:12px 16px;border-bottom:1px solid var(--border);transition:background .1s;cursor:pointer}
.ps-row:last-child{border-bottom:none}
.ps-row:hover{background:var(--bg3)}
.ps-emp-icon{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--orange),#ea580c);color:#fff;font-weight:800;font-size:15px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.ps-badge{font-size:10.5px;font-weight:700;padding:3px 9px;border-radius:99px}
.ps-draft{background:rgba(148,163,184,.15);color:#94a3b8}
.ps-issued{background:rgba(16,185,129,.15);color:#10b981}

/* ── SALARY BUILDER ── */
.sal-builder{border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden}
.sal-row{display:grid;grid-template-columns:1fr 150px 34px;border-bottom:1px solid var(--border)}
.sal-row:last-child{border-bottom:none}
.sal-row input{border:none;background:none;padding:9px 12px;font-size:13px;color:var(--text);font-family:var(--font);width:100%;outline:none}
.sal-row input:focus{background:var(--orange-bg)}
.sal-row .sal-del{display:flex;align-items:center;justify-content:center;background:none;border:none;cursor:pointer;color:var(--text3);font-size:13px;padding:0 8px}
.sal-row .sal-del:hover{color:var(--red);background:rgba(239,68,68,.06)}
.sal-add{font-size:12.5px;color:var(--orange);background:none;border:none;cursor:pointer;padding:8px 12px;font-weight:600;text-align:left;width:100%}
.sal-add:hover{background:var(--orange-bg)}

/* ── NET BOX ── */
.net-box{background:linear-gradient(135deg,#1e293b,#0f172a);border-radius:var(--radius-lg);padding:18px 20px;display:flex;justify-content:space-between;align-items:center;margin-top:16px}
.net-box .net-label{font-size:12px;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.07em}
.net-box .net-val{font-size:28px;font-weight:900;color:#f97316;font-family:var(--font-display)}
.net-box .gross-val{font-size:13px;color:rgba(255,255,255,.5);margin-top:2px}

/* ── PAYSLIP PREVIEW (view mode) ── */
.ps-doc{background:#fff;color:#1e293b;border-radius:8px;padding:0;max-width:720px;margin:0 auto;box-shadow:0 4px 24px rgba(0,0,0,.18);font-family:'Arial',sans-serif;font-size:13px;overflow:hidden}
.ps-doc-hdr{background:#1e293b;padding:22px 28px;display:flex;justify-content:space-between;align-items:flex-start}
.ps-doc-hdr h2{margin:0 0 3px;font-size:18px;color:#fff;font-weight:800}
.ps-doc-hdr .addr{font-size:11px;color:rgba(255,255,255,.55);margin-top:4px;line-height:1.5}
.ps-doc-hdr .ps-label{font-size:24px;font-weight:900;color:#f97316;letter-spacing:2px}
.ps-doc-hdr .ps-meta{font-size:11.5px;color:rgba(255,255,255,.6);margin-top:4px;line-height:1.6;text-align:right}
.ps-doc-body{padding:24px 28px}
.ps-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;padding-bottom:18px;border-bottom:2px solid #e2e8f0;margin-bottom:20px}
.ps-info-section h4{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#64748b;margin:0 0 8px}
.ps-info-section p{margin:0 0 3px;font-size:12.5px;color:#334155;line-height:1.5}
.ps-info-section strong{color:#1e293b}
.ps-salary-grid{display:grid;grid-template-columns:1fr 1px 1fr;gap:0;margin-bottom:20px}
.ps-divider{background:#e2e8f0}
.ps-salary-col{padding:0 20px 0 0}
.ps-salary-col:last-child{padding:0 0 0 20px}
.ps-salary-col h4{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#64748b;margin:0 0 10px;padding-bottom:6px;border-bottom:2px solid #e2e8f0}
.ps-sal-row{display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #f1f5f9;font-size:12.5px}
.ps-sal-row:last-child{border-bottom:none}
.ps-sal-total{display:flex;justify-content:space-between;padding:9px 0 0;font-weight:700;font-size:13px;border-top:2px solid #1e293b;margin-top:6px}
.ps-net-bar{background:#1e293b;padding:16px 28px;display:flex;justify-content:space-between;align-items:center}
.ps-net-bar .lbl{font-size:11px;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.06em}
.ps-net-bar .amt{font-size:26px;font-weight:900;color:#f97316}
.ps-footer-note{padding:10px 28px;background:#f8fafc;font-size:11px;color:#94a3b8;text-align:center;border-top:1px solid #e2e8f0}
.ps-doc-notes{background:#f8fafc;border-left:3px solid #e2e8f0;padding:10px 14px;font-size:12px;color:#64748b;margin-bottom:18px;border-radius:0 4px 4px 0}

@media print{
    .ps-doc{box-shadow:none;border-radius:0;max-width:100%}
    body *:not(.ps-doc):not(.ps-doc *){display:none!important}
    .ps-doc{display:block!important}
}
@media(max-width:900px){.ps-split{grid-template-columns:1fr}}
</style>

<?php if ($single): ?>
<!-- ══════════════════════════════════════════
  VIEW / PRINT PAYSLIP
══════════════════════════════════════════ -->
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap">
  <a href="payslip.php" style="font-size:13px;color:var(--text3);text-decoration:none;display:flex;align-items:center;gap:4px">← Back</a>
  <div style="flex:1;font-size:15px;font-weight:700;font-family:var(--font-display);color:var(--text)">
    <?= h($single['employee_name']) ?> <span style="color:var(--text3);font-weight:400">— <?= h($single['pay_period']) ?></span>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <?php if ($single['status']==='draft'): ?>
    <form method="POST" style="display:inline">
      <input type="hidden" name="action" value="save_payslip">
      <input type="hidden" name="payslip_id" value="<?= $single['id'] ?>">
      <?php // pass all existing data back to just update status ?>
      <button type="submit" name="ps_status" value="issued" class="btn btn-ghost btn-sm" style="color:var(--green);border-color:var(--green)">✅ Mark as Issued</button>
    </form>
    <?php endif; ?>
    <a href="payslip.php?export=print&pid=<?= $single['id'] ?>" target="_blank"
       class="btn btn-primary btn-sm" style="background:#1e293b;border-color:#1e293b">🖨 Print / PDF</a>
    <a href="payslip.php?export=docx&pid=<?= $single['id'] ?>"
       class="btn btn-ghost btn-sm">⬇ DOCX</a>
    <a href="payslip.php?edit=<?= $single['id'] ?>"
       class="btn btn-ghost btn-sm">✎ Edit</a>
    <?php if (isAdmin()): ?>
    <form method="POST" style="display:inline" onsubmit="return confirm('Permanently delete this payslip?')">
      <input type="hidden" name="action" value="delete_payslip">
      <input type="hidden" name="payslip_id" value="<?= $single['id'] ?>">
      <button type="submit" class="btn btn-danger btn-sm">🗑</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<?php
$allowances = json_decode($single['allowances']??'[]',true) ?: [];
$deductions  = json_decode($single['deductions']??'[]', true) ?: [];
$sym         = htmlspecialchars($single['currency']??'LKR',ENT_QUOTES);
?>
<!-- PAYSLIP DOCUMENT -->
<div class="ps-doc" id="ps-doc">
  <div class="ps-doc-hdr">
    <div>
      <?php if ($single['company_logo'] && file_exists($single['company_logo'])): ?>
      <img src="<?= h($single['company_logo']) ?>" style="height:40px;margin-bottom:8px;display:block">
      <?php endif; ?>
      <h2><?= h($single['company_name'] ?? 'Company') ?></h2>
      <?php if ($single['company_address']): ?><div class="addr"><?= nl2br(h($single['company_address'])) ?></div><?php endif; ?>
    </div>
    <div>
      <div class="ps-label">PAYSLIP</div>
      <div class="ps-meta">
        Pay Period: <strong style="color:#fff"><?= h($single['pay_period']) ?></strong><br>
        <?php if ($single['pay_date']): ?>Pay Date: <strong style="color:#fff"><?= date('d M Y',strtotime($single['pay_date'])) ?></strong><br><?php endif; ?>
        Status: <strong style="color:<?= $single['status']==='issued'?'#10b981':'#f59e0b' ?>"><?= ucfirst($single['status']) ?></strong>
      </div>
    </div>
  </div>

  <div class="ps-doc-body">
    <div class="ps-info-grid">
      <div class="ps-info-section">
        <h4>Employee Details</h4>
        <p><strong><?= h($single['employee_name']) ?></strong></p>
        <?php if ($single['designation']): ?><p><?= h($single['designation']) ?></p><?php endif; ?>
        <?php if ($single['department']): ?><p><?= h($single['department']) ?></p><?php endif; ?>
        <?php if ($single['employee_id_no']): ?><p>EMP ID: <?= h($single['employee_id_no']) ?></p><?php endif; ?>
        <?php if ($single['employee_email']): ?><p><?= h($single['employee_email']) ?></p><?php endif; ?>
        <?php if ($single['employee_phone']): ?><p><?= h($single['employee_phone']) ?></p><?php endif; ?>
      </div>
      <div class="ps-info-section">
        <h4>Bank / Payment Details</h4>
        <?php if ($single['bank_name']): ?><p>Bank: <strong><?= h($single['bank_name']) ?></strong></p><?php endif; ?>
        <?php if ($single['account_no']): ?><p>Account: <strong><?= h($single['account_no']) ?></strong></p><?php endif; ?>
        <p style="margin-top:10px">Basic Salary: <strong><?= $sym ?> <?= number_format($single['basic_salary'],2) ?></strong></p>
        <p>Gross Salary: <strong><?= $sym ?> <?= number_format($single['gross_salary'],2) ?></strong></p>
      </div>
    </div>

    <?php if ($single['notes']): ?>
    <div class="ps-doc-notes"><?= h($single['notes']) ?></div>
    <?php endif; ?>

    <div class="ps-salary-grid">
      <div class="ps-salary-col">
        <h4>Earnings</h4>
        <div class="ps-sal-row"><span>Basic Salary</span><span><?= $sym ?> <?= number_format($single['basic_salary'],2) ?></span></div>
        <?php foreach ($allowances as $a): ?>
        <div class="ps-sal-row"><span><?= h($a['name']) ?></span><span><?= $sym ?> <?= number_format($a['amount'],2) ?></span></div>
        <?php endforeach; ?>
        <div class="ps-sal-total"><span>Gross Salary</span><span><?= $sym ?> <?= number_format($single['gross_salary'],2) ?></span></div>
      </div>
      <div class="ps-divider"></div>
      <div class="ps-salary-col">
        <h4>Deductions</h4>
        <?php if ($deductions): ?>
          <?php foreach ($deductions as $d): ?>
          <div class="ps-sal-row" style="color:#ef4444">
            <span><?= h($d['name']) ?></span>
            <span>- <?= $sym ?> <?= number_format($d['amount'],2) ?></span>
          </div>
          <?php endforeach; ?>
          <div class="ps-sal-total"><span>Total Deductions</span><span style="color:#ef4444">- <?= $sym ?> <?= number_format($single['total_deductions'],2) ?></span></div>
        <?php else: ?>
          <div style="color:#94a3b8;font-size:12px;padding:8px 0">No deductions for this period</div>
          <div class="ps-sal-total"><span>Total Deductions</span><span><?= $sym ?> 0.00</span></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="ps-net-bar">
    <div>
      <div class="lbl">Net Salary Payable</div>
      <div style="font-size:12px;color:rgba(255,255,255,.5);margin-top:2px"><?= h($single['pay_period']) ?></div>
    </div>
    <div class="amt"><?= $sym ?> <?= number_format($single['net_salary'],2) ?></div>
  </div>

  <?php if ($single['footer_note']): ?>
  <div class="ps-footer-note"><?= h($single['footer_note']) ?></div>
  <?php else: ?>
  <div class="ps-footer-note">This is a computer-generated payslip and requires no signature.</div>
  <?php endif; ?>
</div>

<?php else: ?>
<!-- ══════════════════════════════════════════
  TABS: LIST / CREATE / TEMPLATES
══════════════════════════════════════════ -->
<div class="ps-tabs">
  <button class="ps-tab <?= $section==='list'?'active':'' ?>"      onclick="psTab('list')">📋 Payslips <span id="ps-count-badge" style="font-size:10px;background:var(--bg4);padding:1px 6px;border-radius:99px;margin-left:2px"><?= count($payslips) ?></span></button>
  <button class="ps-tab <?= $section==='create'?'active':'' ?>"    onclick="psTab('create')">➕ <?= $ep?'Edit Payslip':'Create Payslip' ?></button>
  <button class="ps-tab <?= $section==='templates'?'active':'' ?>" onclick="psTab('templates')">⚙ Templates</button>
</div>

<!-- ─── PAYSLIP LIST ─── -->
<div id="pssec-list" style="display:<?= $section==='list'?'block':'none' ?>">
  <div style="display:flex;justify-content:flex-end;margin-bottom:14px">
    <button onclick="psTab('create')" class="btn btn-primary">＋ New Payslip</button>
  </div>
  <?php if (!$payslips): ?>
  <div class="empty-state"><div class="icon">💵</div><p>No payslips generated yet. Click <strong>+ New Payslip</strong> to create the first one.</p></div>
  <?php else: ?>
  <div class="card" style="padding:0">
    <?php foreach ($payslips as $ps):
      $sc = $ps['status']==='issued'?'ps-issued':'ps-draft';
      $init = strtoupper(substr($ps['employee_name'],0,1));
    ?>
    <div class="ps-row" onclick="location.href='payslip.php?view=<?= $ps['id'] ?>'">
      <div class="ps-emp-icon"><?= $init ?></div>
      <div style="flex:1;min-width:0">
        <div style="font-size:13.5px;font-weight:700;color:var(--text)"><?= h($ps['employee_name']) ?></div>
        <div style="font-size:11.5px;color:var(--text3)">
          <?= h($ps['pay_period']) ?>
          <?= $ps['designation']?' · '.h($ps['designation']):'' ?>
          <?= $ps['company_name']?' · '.h($ps['company_name']):'' ?>
        </div>
      </div>
      <div style="text-align:right;flex-shrink:0">
        <div style="font-size:15px;font-weight:800;color:var(--text);font-family:var(--font-display)"><?= h($ps['currency']) ?> <?= number_format($ps['net_salary'],2) ?></div>
        <div style="font-size:11px;color:var(--text3)">Net Salary</div>
      </div>
      <span class="ps-badge <?= $sc ?>"><?= ucfirst($ps['status']) ?></span>
      <div style="display:flex;gap:6px;flex-shrink:0" onclick="event.stopPropagation()">
        <a href="payslip.php?view=<?= $ps['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="View">👁</a>
        <a href="payslip.php?export=print&pid=<?= $ps['id'] ?>" target="_blank" class="btn btn-ghost btn-sm btn-icon" title="Print/PDF">🖨</a>
        <a href="payslip.php?export=docx&pid=<?= $ps['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Download DOCX">⬇</a>
        <a href="payslip.php?edit=<?= $ps['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Edit">✎</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- ─── CREATE / EDIT PAYSLIP ─── -->
<div id="pssec-create" style="display:<?= $section==='create'?'block':'none' ?>">
  <form method="POST" id="ps-form">
    <input type="hidden" name="action" value="save_payslip">
    <?php if ($ep): ?><input type="hidden" name="payslip_id" value="<?= $ep['id'] ?>"><?php endif; ?>

    <div class="ps-split">
      <!-- LEFT COLUMN -->
      <div>
        <!-- Employee Panel -->
        <div class="ps-panel" style="margin-bottom:16px">
          <div class="ps-panel-head">
            <div class="ps-panel-title">👤 Employee Details</div>
          </div>
          <div class="ps-panel-body">
            <div class="form-group">
              <label class="form-label">Auto-fill from CRM Users</label>
              <select class="form-control" onchange="fillEmp(this)">
                <option value="">— Select or fill manually below —</option>
                <?php foreach ($employees as $e): ?>
                <option value="<?= $e['id'] ?>"
                  data-name="<?= h($e['name']) ?>"
                  data-email="<?= h($e['email']) ?>"
                  data-phone="<?= h($e['phone']??'') ?>"
                  data-dept="<?= h($e['department']??'') ?>"
                  <?= ($ep&&$ep['employee_id']==$e['id'])?'selected':'' ?>>
                  <?= h($e['name']) ?><?= $e['department']?' — '.h($e['department']):'' ?>
                </option>
                <?php endforeach; ?>
              </select>
              <input type="hidden" name="employee_id" id="emp-id-hidden" value="<?= h($ep['employee_id']??'') ?>">
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Full Name <span style="color:var(--red)">*</span></label>
                <input type="text" name="employee_name" class="form-control" required placeholder="Employee full name" value="<?= h($ep['employee_name']??'') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Employee ID</label>
                <input type="text" name="employee_id_no" class="form-control" placeholder="EMP-001" value="<?= h($ep['employee_id_no']??'') ?>">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Designation / Title</label>
                <input type="text" name="designation" class="form-control" placeholder="e.g. Software Developer" value="<?= h($ep['designation']??'') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Department</label>
                <input type="text" name="department" class="form-control" placeholder="e.g. Engineering" value="<?= h($ep['department']??'') ?>">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="employee_email" class="form-control" placeholder="employee@company.com" value="<?= h($ep['employee_email']??'') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" name="employee_phone" class="form-control" placeholder="+94 71 234 5678" value="<?= h($ep['employee_phone']??'') ?>">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Bank Name</label>
                <input type="text" name="bank_name" class="form-control" placeholder="e.g. Commercial Bank" value="<?= h($ep['bank_name']??'') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Account Number</label>
                <input type="text" name="account_no" class="form-control" placeholder="XXXX-XXXX-XXXX" value="<?= h($ep['account_no']??'') ?>">
              </div>
            </div>
          </div>
        </div>

        <!-- Pay Period Panel -->
        <div class="ps-panel">
          <div class="ps-panel-head">
            <div class="ps-panel-title">📅 Pay Period & Settings</div>
          </div>
          <div class="ps-panel-body">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Pay Period <span style="color:var(--red)">*</span></label>
                <input type="text" name="pay_period" class="form-control" required placeholder="e.g. April 2026" value="<?= h($ep['pay_period']??date('F Y')) ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Pay Date</label>
                <input type="date" name="pay_date" class="form-control" value="<?= h($ep['pay_date']??date('Y-m-d')) ?>">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Currency</label>
                <select name="currency" class="form-control">
                  <?php foreach (['LKR'=>'LKR — Sri Lankan Rupee','USD'=>'USD — US Dollar','INR'=>'INR — Indian Rupee','SGD'=>'SGD — Singapore Dollar','AED'=>'AED — UAE Dirham','GBP'=>'GBP — British Pound','EUR'=>'EUR — Euro'] as $cv=>$cl): ?>
                  <option value="<?= $cv ?>" <?= ($ep['currency']??'LKR')===$cv?'selected':'' ?>><?= $cl ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Company Template</label>
                <select name="template_id" class="form-control">
                  <option value="">— No template —</option>
                  <?php foreach ($templates as $t): ?>
                  <option value="<?= $t['id'] ?>" <?= ($ep['template_id']??($default_tpl['id']??''))==$t['id']?'selected':'' ?>><?= h($t['name']) ?><?= $t['is_default']?' ✓':'' ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Status</label>
                <select name="ps_status" class="form-control">
                  <option value="draft"  <?= ($ep['status']??'draft')==='draft'?'selected':'' ?>>📝 Draft</option>
                  <option value="issued" <?= ($ep['status']??'')==='issued'?'selected':'' ?>>✅ Issued</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Internal Notes</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes (not shown on payslip header)"><?= h($ep['notes']??'') ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN: Salary -->
      <div>
        <div class="ps-panel" style="margin-bottom:16px">
          <div class="ps-panel-head">
            <div class="ps-panel-title">💰 Basic Salary</div>
          </div>
          <div class="ps-panel-body">
            <div class="form-group">
              <label class="form-label">Basic Salary <span style="color:var(--red)">*</span></label>
              <input type="number" name="basic_salary" id="ps-basic" class="form-control"
                style="font-size:18px;font-weight:700;height:48px"
                step="0.01" min="0" required placeholder="0.00"
                value="<?= $ep['basic_salary']??'' ?>" oninput="psCalc()">
            </div>
          </div>
        </div>

        <div class="ps-panel" style="margin-bottom:16px">
          <div class="ps-panel-head">
            <div class="ps-panel-title">➕ Allowances</div>
            <span style="font-size:11.5px;color:var(--text3)">Added to gross</span>
          </div>
          <div class="ps-panel-body" style="padding:0">
            <div id="allow-builder" class="sal-builder">
              <?php foreach ($ep_allow as $a): ?>
              <div class="sal-row">
                <input type="text"   name="allowance_name[]"   value="<?= h($a['name']) ?>"  placeholder="e.g. House Allowance">
                <input type="number" name="allowance_amount[]" value="<?= ($a['amount']>0?$a['amount']:'') ?>" placeholder="0.00" step="0.01" min="0" oninput="psCalc()">
                <button type="button" class="sal-del" onclick="psDelRow(this)" title="Remove">✕</button>
              </div>
              <?php endforeach; ?>
            </div>
            <button type="button" class="sal-add" onclick="psAddRow('allow')">＋ Add Allowance</button>
          </div>
        </div>

        <div class="ps-panel" style="margin-bottom:0">
          <div class="ps-panel-head">
            <div class="ps-panel-title">➖ Deductions</div>
            <span style="font-size:11.5px;color:var(--text3)">Subtracted from gross</span>
          </div>
          <div class="ps-panel-body" style="padding:0">
            <div id="ded-builder" class="sal-builder">
              <?php foreach ($ep_ded as $d): ?>
              <div class="sal-row">
                <input type="text"   name="deduction_name[]"   value="<?= h($d['name']) ?>"  placeholder="e.g. EPF (8%)">
                <input type="number" name="deduction_amount[]" value="<?= ($d['amount']>0?$d['amount']:'') ?>" placeholder="0.00" step="0.01" min="0" oninput="psCalc()">
                <button type="button" class="sal-del" onclick="psDelRow(this)" title="Remove">✕</button>
              </div>
              <?php endforeach; ?>
            </div>
            <button type="button" class="sal-add" onclick="psAddRow('ded')">＋ Add Deduction</button>
          </div>
        </div>

        <!-- Live Summary -->
        <div class="net-box">
          <div>
            <div class="net-label">Net Salary</div>
            <div class="net-val" id="ps-net-val">0.00</div>
            <div class="gross-val">Gross: <span id="ps-gross-val">0.00</span> &nbsp;|&nbsp; Deductions: <span id="ps-ded-val">0.00</span></div>
          </div>
          <div style="text-align:right">
            <div style="font-size:11px;color:rgba(255,255,255,.5);margin-bottom:4px">SAVE PAYSLIP</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end">
              <button type="submit" name="ps_status" value="draft" class="btn btn-ghost btn-sm" style="color:#fff;border-color:rgba(255,255,255,.3)">💾 Save Draft</button>
              <button type="submit" name="ps_status" value="issued" class="btn btn-sm" style="background:#f97316;color:#fff;border:none">✅ Save & Issue</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<!-- ─── TEMPLATES ─── -->
<div id="pssec-templates" style="display:<?= $section==='templates'?'block':'none' ?>">
  <div class="ps-split">
    <div>
      <div style="font-size:13px;font-weight:700;margin-bottom:14px;color:var(--text)">Company Templates</div>
      <?php if (!$templates): ?>
      <div class="empty-state"><div class="icon">📋</div><p>No templates yet. Create one on the right.</p></div>
      <?php else: ?>
      <?php foreach ($templates as $t): ?>
      <div class="card" style="margin-bottom:10px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:10px">
        <div style="display:flex;align-items:center;gap:12px">
          <div style="width:38px;height:38px;border-radius:8px;background:var(--orange-bg);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">🏢</div>
          <div>
            <div style="font-weight:700;font-size:13px;color:var(--text)"><?= h($t['name']) ?>
              <?php if ($t['is_default']): ?><span style="font-size:10px;background:var(--orange-bg);color:var(--orange);padding:1px 6px;border-radius:99px;margin-left:5px">Default</span><?php endif; ?>
            </div>
            <div style="font-size:12px;color:var(--text3)"><?= h($t['company_name']) ?><?= $t['company_address']?' · '.h(mb_substr($t['company_address'],0,30)):'' ?></div>
          </div>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0">
          <a href="payslip.php?section=templates&edit_tpl=<?= $t['id'] ?>" class="btn btn-ghost btn-sm">✎ Edit</a>
          <?php if (isAdmin()): ?>
          <form method="POST" onsubmit="return confirm('Delete template?')" style="display:inline">
            <input type="hidden" name="action" value="delete_template">
            <input type="hidden" name="template_id" value="<?= $t['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm btn-icon">🗑</button>
          </form>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="ps-panel">
      <div class="ps-panel-head">
        <div class="ps-panel-title"><?= $et?'✎ Edit Template':'➕ New Template' ?></div>
      </div>
      <div class="ps-panel-body">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="save_template">
          <?php if ($et): ?><input type="hidden" name="template_id" value="<?= $et['id'] ?>"><?php endif; ?>
          <div class="form-group">
            <label class="form-label">Template Name <span style="color:var(--red)">*</span></label>
            <input type="text" name="tpl_name" class="form-control" required value="<?= h($et['name']??'') ?>" placeholder="e.g. Standard Template">
          </div>
          <div class="form-group">
            <label class="form-label">Company Name <span style="color:var(--red)">*</span></label>
            <input type="text" name="company_name" class="form-control" required value="<?= h($et['company_name']??'') ?>" placeholder="Your company name">
          </div>
          <div class="form-group">
            <label class="form-label">Company Address</label>
            <textarea name="company_address" class="form-control" rows="3" placeholder="Full company address"><?= h($et['company_address']??'') ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Company Logo <span style="font-size:11px;color:var(--text3)">(PNG or JPG, shown on payslip header)</span></label>
            <?php if ($et&&$et['company_logo']&&file_exists($et['company_logo'])): ?>
            <div style="margin-bottom:8px"><img src="<?= h($et['company_logo']) ?>" style="height:36px;border-radius:4px;border:1px solid var(--border)"> <span style="font-size:11px;color:var(--text3)">current logo</span></div>
            <?php endif; ?>
            <input type="file" name="company_logo" class="form-control" accept=".png,.jpg,.jpeg">
          </div>
          <div class="form-group">
            <label class="form-label">Footer Note</label>
            <input type="text" name="footer_note" class="form-control" value="<?= h($et['footer_note']??'This is a computer-generated payslip and requires no signature.') ?>">
          </div>
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--text2)">
              <input type="checkbox" name="is_default" value="1" style="accent-color:var(--orange)" <?= ($et['is_default']??0)?'checked':'' ?>>
              Set as default template for new payslips
            </label>
          </div>
          <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary" style="flex:1">💾 Save Template</button>
            <?php if ($et): ?><a href="payslip.php?section=templates" class="btn btn-ghost">Cancel</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
// ── TAB SWITCHING (fixed: use pssec- prefix to avoid ID clash with buttons) ──
function psTab(name) {
    ['list','create','templates'].forEach(function(n) {
        var sec = document.getElementById('pssec-'+n);
        if (sec) sec.style.display = (n === name) ? 'block' : 'none';
    });
    document.querySelectorAll('.ps-tab').forEach(function(btn, i) {
        btn.classList.toggle('active', ['list','create','templates'][i] === name);
    });
    if (name === 'create') {
        var b = document.getElementById('ps-basic');
        if (b) { psCalc(); b.focus(); }
    }
}

// ── AUTO-FILL EMPLOYEE ──
function fillEmp(sel) {
    var opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;
    document.querySelector('[name=employee_name]').value  = opt.dataset.name  || '';
    document.querySelector('[name=employee_email]').value = opt.dataset.email || '';
    document.querySelector('[name=employee_phone]').value = opt.dataset.phone || '';
    document.querySelector('[name=department]').value     = opt.dataset.dept  || '';
    document.getElementById('emp-id-hidden').value        = opt.value;
}

// ── ADD / DELETE SALARY ROWS ──
function psAddRow(type) {
    var b   = document.getElementById(type === 'allow' ? 'allow-builder' : 'ded-builder');
    var pfx = type === 'allow' ? 'allowance' : 'deduction';
    var row = document.createElement('div');
    row.className = 'sal-row';
    row.innerHTML = '<input type="text" name="'+pfx+'_name[]" placeholder="Description">'
        + '<input type="number" name="'+pfx+'_amount[]" placeholder="0.00" step="0.01" min="0" oninput="psCalc()">'
        + '<button type="button" class="sal-del" onclick="psDelRow(this)" title="Remove">✕</button>';
    b.appendChild(row);
    row.querySelector('input').focus();
}

function psDelRow(btn) {
    btn.closest('.sal-row').remove();
    psCalc();
}

// ── LIVE SALARY CALCULATOR ──
function psCalc() {
    var basic = parseFloat(document.getElementById('ps-basic')?.value) || 0;
    var allow = 0;
    document.querySelectorAll('[name="allowance_amount[]"]').forEach(function(i) { allow += parseFloat(i.value) || 0; });
    var ded = 0;
    document.querySelectorAll('[name="deduction_amount[]"]').forEach(function(i) { ded += parseFloat(i.value) || 0; });
    var gross = basic + allow;
    var net   = gross - ded;
    var fmt   = function(n) { return n.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}); };
    var nv = document.getElementById('ps-net-val');   if (nv) nv.textContent   = fmt(net);
    var gv = document.getElementById('ps-gross-val'); if (gv) gv.textContent   = fmt(gross);
    var dv = document.getElementById('ps-ded-val');   if (dv) dv.textContent   = fmt(ded);
    // Colour net salary
    if (nv) nv.style.color = net < 0 ? '#ef4444' : '#f97316';
}

document.addEventListener('DOMContentLoaded', psCalc);
</script>

<?php renderLayoutEnd(); ?>