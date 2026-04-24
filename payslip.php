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

    // Save template
    if ($action === 'save_template') {
        $name  = trim($_POST['tpl_name'] ?? '');
        $cname = trim($_POST['company_name'] ?? '');
        $caddr = trim($_POST['company_address'] ?? '');
        $foot  = trim($_POST['footer_note'] ?? '');
        $def   = (int)($_POST['is_default'] ?? 0);
        if ($name && $cname) {
            $logo = '';
            if (!empty($_FILES['company_logo']['tmp_name'])) {
                $ext = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['png','jpg','jpeg','gif'])) {
                    $dir = UPLOAD_DOC_DIR;
                    $fn  = 'logo_'.uniqid().'.'.$ext;
                    move_uploaded_file($_FILES['company_logo']['tmp_name'], $dir.$fn);
                    $logo = 'uploads/documents/'.$fn;
                }
            }
            $tid = (int)($_POST['template_id'] ?? 0);
            if ($def) $db->query("UPDATE payslip_templates SET is_default=0");
            if ($tid) {
                $stmt = $db->prepare("UPDATE payslip_templates SET name=?,company_name=?,company_address=?,footer_note=?,is_default=? WHERE id=$tid AND created_by=$uid");
                $stmt->bind_param("ssssi",$name,$cname,$caddr,$foot,$def);
                $stmt->execute();
            } else {
                if ($logo) {
                    $stmt = $db->prepare("INSERT INTO payslip_templates (name,company_name,company_address,company_logo,footer_note,is_default,created_by) VALUES (?,?,?,?,?,?,?)");
                    $stmt->bind_param("sssssii",$name,$cname,$caddr,$logo,$foot,$def,$uid);
                } else {
                    $stmt = $db->prepare("INSERT INTO payslip_templates (name,company_name,company_address,footer_note,is_default,created_by) VALUES (?,?,?,?,?,?)");
                    $stmt->bind_param("ssssii",$name,$cname,$caddr,$foot,$def,$uid);
                }
                $stmt->execute();
            }
            flash('Template saved.','success');
        }
        ob_end_clean(); header('Location: payslip.php'); exit;
    }

    // Delete template
    if ($action === 'delete_template' && isAdmin()) {
        $tid = (int)($_POST['template_id'] ?? 0);
        $db->query("DELETE FROM payslip_templates WHERE id=$tid");
        flash('Template deleted.','success');
        ob_end_clean(); header('Location: payslip.php'); exit;
    }

    // Save payslip
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
        $status    = $_POST['status'] ?? 'draft';

        // Build allowances + deductions from dynamic rows
        $allowances  = []; $deductions = [];
        $all_names   = $_POST['allowance_name']  ?? [];
        $all_amounts = $_POST['allowance_amount'] ?? [];
        $ded_names   = $_POST['deduction_name']   ?? [];
        $ded_amounts = $_POST['deduction_amount']  ?? [];
        foreach ($all_names as $i => $n) {
            if (trim($n) && (float)($all_amounts[$i]??0) > 0)
                $allowances[] = ['name'=>trim($n),'amount'=>(float)$all_amounts[$i]];
        }
        foreach ($ded_names as $i => $n) {
            if (trim($n) && (float)($ded_amounts[$i]??0) > 0)
                $deductions[] = ['name'=>trim($n),'amount'=>(float)$ded_amounts[$i]];
        }
        $total_allow  = array_sum(array_column($allowances,'amount'));
        $total_ded    = array_sum(array_column($deductions,'amount'));
        $gross        = $basic + $total_allow;
        $net          = $gross - $total_ded;
        $allow_json   = json_encode($allowances);
        $ded_json     = json_encode($deductions);

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
        flash('Payslip saved.','success');
        ob_end_clean(); header("Location: payslip.php?view=$new_pid"); exit;
    }

    // Delete payslip
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
    require_once 'payslip_export.php';
    exportPayslip($db, $pid, $fmt);
    exit;
}

// ── DATA ──
$view_id = (int)($_GET['view'] ?? 0);
$tab     = $_GET['tab'] ?? 'list';

$payslips = $db->query("
    SELECT p.*, t.company_name
    FROM payslips p
    LEFT JOIN payslip_templates t ON t.id=p.template_id
    ORDER BY p.created_at DESC LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

$templates = $db->query("SELECT * FROM payslip_templates ORDER BY is_default DESC, name ASC")->fetch_all(MYSQLI_ASSOC);
$default_tpl = null;
foreach ($templates as $t) { if ($t['is_default']) { $default_tpl=$t; break; } }
if (!$default_tpl && $templates) $default_tpl = $templates[0];

$single = null;
if ($view_id) {
    $single = $db->query("SELECT p.*, t.company_name, t.company_address, t.company_logo, t.footer_note FROM payslips p LEFT JOIN payslip_templates t ON t.id=p.template_id WHERE p.id=$view_id")->fetch_assoc();
}

$employees = $db->query("SELECT id,name,email,phone,department FROM users WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

renderLayout('Payslip Generator','payslip');
?>
<style>
.ps-tabs{display:flex;gap:2px;border-bottom:1px solid var(--border);margin-bottom:20px}
.ps-tab{padding:8px 18px;font-size:13px;font-weight:600;color:var(--text3);cursor:pointer;border:none;background:none;border-bottom:2px solid transparent;transition:color .15s}
.ps-tab.active{color:var(--orange);border-bottom-color:var(--orange)}
.ps-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.ps-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
.ps-card-head{padding:13px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--bg3)}
.ps-card-title{font-size:13px;font-weight:700;font-family:var(--font-display)}
.ps-card-body{padding:16px}
.ps-row-item{display:flex;align-items:center;justify-content:space-between;padding:9px 14px;border-bottom:1px solid var(--border);transition:background .1s}
.ps-row-item:last-child{border-bottom:none}
.ps-row-item:hover{background:var(--bg3)}
.ps-status{font-size:11px;font-weight:700;padding:2px 8px;border-radius:99px}
.ps-draft{background:rgba(148,163,184,.15);color:#94a3b8}
.ps-issued{background:rgba(16,185,129,.15);color:#10b981}

/* Payslip preview */
.ps-preview{background:#fff;color:#1a1a1a;border-radius:8px;padding:32px;max-width:680px;margin:0 auto;box-shadow:0 2px 20px rgba(0,0,0,.15);font-family:Arial,sans-serif;font-size:13px}
.ps-preview table{width:100%;border-collapse:collapse}
.ps-preview td{padding:5px 8px}
.ps-preview .hdr{background:#1e293b;color:#fff;padding:16px 20px;border-radius:6px 6px 0 0;margin:-32px -32px 20px -32px;display:flex;justify-content:space-between;align-items:center}
.ps-preview .hdr h2{margin:0;font-size:17px;color:#fff}
.ps-preview .hdr .period{font-size:12px;color:rgba(255,255,255,.7)}
.ps-preview .section-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;padding:10px 0 4px;border-bottom:2px solid #e2e8f0;margin-bottom:6px}
.ps-preview .earn-row{display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #f1f5f9}
.ps-preview .total-row{display:flex;justify-content:space-between;padding:8px 0;font-weight:700;border-top:2px solid #1e293b;margin-top:6px}
.ps-preview .net-box{background:#1e293b;color:#fff;padding:14px 20px;border-radius:0 0 6px 6px;margin:20px -32px -32px -32px;display:flex;justify-content:space-between;align-items:center}
.ps-preview .net-box .lbl{font-size:12px;opacity:.7}
.ps-preview .net-box .amt{font-size:22px;font-weight:800}

/* Dynamic row builder */
.earn-row-builder{border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden}
.earn-row-builder .row-line{display:grid;grid-template-columns:1fr 140px 32px;gap:0;border-bottom:1px solid var(--border)}
.earn-row-builder .row-line:last-child{border-bottom:none}
.earn-row-builder input{border:none;background:none;padding:8px 10px;font-size:13px;color:var(--text);font-family:var(--font);width:100%}
.earn-row-builder input:focus{outline:1px solid var(--orange);border-radius:2px}
.earn-row-builder .del-row{display:flex;align-items:center;justify-content:center;background:none;border:none;cursor:pointer;color:var(--text3);width:32px}
.earn-row-builder .del-row:hover{color:var(--red)}
.add-row-btn{font-size:12px;color:var(--orange);background:none;border:none;cursor:pointer;padding:6px 10px;font-weight:600}
.add-row-btn:hover{text-decoration:underline}

@media(max-width:800px){.ps-grid{grid-template-columns:1fr}}
</style>

<?php if ($single): ?>
<!-- ══ SINGLE PAYSLIP VIEW ══ -->
<div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;flex-wrap:wrap">
  <a href="payslip.php" style="color:var(--text3);font-size:13px;text-decoration:none">← All Payslips</a>
  <div style="flex:1;font-size:15px;font-weight:700;font-family:var(--font-display)"><?= h($single['employee_name']) ?> — <?= h($single['pay_period']) ?></div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="payslip.php?export=print&pid=<?= $single['id'] ?>" target="_blank" class="btn btn-ghost btn-sm">🖨 Print / Save PDF</a>
    <a href="payslip.php?export=docx&pid=<?= $single['id'] ?>" class="btn btn-ghost btn-sm">⬇ Download DOCX</a>
    <a href="payslip.php?edit=<?= $single['id'] ?>" class="btn btn-primary btn-sm">✎ Edit</a>
    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this payslip?')">
      <input type="hidden" name="action" value="delete_payslip">
      <input type="hidden" name="payslip_id" value="<?= $single['id'] ?>">
      <button type="submit" class="btn btn-danger btn-sm">🗑</button>
    </form>
  </div>
</div>

<?php
$allowances = json_decode($single['allowances'] ?? '[]', true) ?: [];
$deductions  = json_decode($single['deductions']  ?? '[]', true) ?: [];
$sym = $single['currency'] ?? 'LKR';
?>
<!-- PAYSLIP PREVIEW -->
<div class="ps-preview" id="ps-print-area">
  <div class="hdr">
    <div>
      <?php if ($single['company_logo'] && file_exists($single['company_logo'])): ?>
      <img src="<?= h($single['company_logo']) ?>" style="height:36px;margin-bottom:4px;display:block">
      <?php endif; ?>
      <h2><?= h($single['company_name'] ?? 'Payslip') ?></h2>
      <?php if ($single['company_address']): ?>
      <div style="font-size:11px;color:rgba(255,255,255,.65)"><?= h($single['company_address']) ?></div>
      <?php endif; ?>
    </div>
    <div style="text-align:right">
      <div style="font-size:16px;font-weight:700;color:#f97316">PAYSLIP</div>
      <div class="period">Pay Period: <?= h($single['pay_period']) ?></div>
      <?php if ($single['pay_date']): ?><div class="period">Pay Date: <?= date('d M Y',strtotime($single['pay_date'])) ?></div><?php endif; ?>
    </div>
  </div>

  <!-- Employee info -->
  <table style="margin-bottom:16px">
    <tr>
      <td style="width:50%;vertical-align:top">
        <div class="section-title">Employee Details</div>
        <div><strong><?= h($single['employee_name']) ?></strong></div>
        <?php if ($single['designation']): ?><div style="color:#64748b"><?= h($single['designation']) ?></div><?php endif; ?>
        <?php if ($single['department']): ?><div style="color:#64748b"><?= h($single['department']) ?></div><?php endif; ?>
        <?php if ($single['employee_id_no']): ?><div>ID: <?= h($single['employee_id_no']) ?></div><?php endif; ?>
      </td>
      <td style="width:50%;vertical-align:top;padding-left:24px">
        <div class="section-title">Payment Info</div>
        <?php if ($single['bank_name']): ?><div>Bank: <?= h($single['bank_name']) ?></div><?php endif; ?>
        <?php if ($single['account_no']): ?><div>Account: <?= h($single['account_no']) ?></div><?php endif; ?>
        <?php if ($single['employee_email']): ?><div><?= h($single['employee_email']) ?></div><?php endif; ?>
      </td>
    </tr>
  </table>

  <!-- Earnings & Deductions -->
  <table>
    <tr>
      <td style="width:50%;vertical-align:top;padding-right:16px">
        <div class="section-title">Earnings</div>
        <div class="earn-row"><span>Basic Salary</span><span><?= $sym ?> <?= number_format($single['basic_salary'],2) ?></span></div>
        <?php foreach ($allowances as $a): ?>
        <div class="earn-row"><span><?= h($a['name']) ?></span><span><?= $sym ?> <?= number_format($a['amount'],2) ?></span></div>
        <?php endforeach; ?>
        <div class="total-row"><span>Gross Salary</span><span><?= $sym ?> <?= number_format($single['gross_salary'],2) ?></span></div>
      </td>
      <td style="width:50%;vertical-align:top;padding-left:16px;border-left:2px solid #e2e8f0">
        <div class="section-title">Deductions</div>
        <?php if ($deductions): ?>
        <?php foreach ($deductions as $d): ?>
        <div class="earn-row"><span><?= h($d['name']) ?></span><span><?= $sym ?> <?= number_format($d['amount'],2) ?></span></div>
        <?php endforeach; ?>
        <div class="total-row"><span>Total Deductions</span><span><?= $sym ?> <?= number_format($single['total_deductions'],2) ?></span></div>
        <?php else: ?>
        <div style="color:#94a3b8;padding:8px 0;font-size:12px">No deductions</div>
        <?php endif; ?>
      </td>
    </tr>
  </table>

  <?php if ($single['notes']): ?>
  <div style="margin-top:14px;padding:10px;background:#f8fafc;border-radius:4px;font-size:12px;color:#64748b"><?= h($single['notes']) ?></div>
  <?php endif; ?>

  <!-- Net salary -->
  <div class="net-box">
    <div><div class="lbl">Net Salary</div><div class="lbl"><?= h($single['pay_period']) ?></div></div>
    <div class="amt"><?= $sym ?> <?= number_format($single['net_salary'],2) ?></div>
  </div>
  <?php if ($single['footer_note']): ?>
  <div style="margin-top:14px;font-size:11px;color:#94a3b8;text-align:center"><?= h($single['footer_note']) ?></div>
  <?php endif; ?>
</div>

<?php else: ?>
<!-- ══ LIST / CREATE TABS ══ -->
<div class="ps-tabs">
  <button class="ps-tab <?= $tab!=='create'&&!isset($_GET['templates'])?'active':'' ?>" onclick="showTab('list')">📋 Payslips</button>
  <button class="ps-tab <?= $tab==='create'?'active':'' ?>" onclick="showTab('create')" id="tab-create">➕ Create Payslip</button>
  <button class="ps-tab <?= isset($_GET['templates'])?'active':'' ?>" onclick="showTab('templates')">⚙ Templates</button>
</div>

<!-- LIST TAB -->
<div id="tab-list" class="<?= $tab==='create'||isset($_GET['templates'])?'hidden':'' ?>">
  <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
    <button onclick="showTab('create')" class="btn btn-primary">＋ New Payslip</button>
  </div>
  <?php if (!$payslips): ?>
  <div class="empty-state"><div class="icon">💰</div><p>No payslips yet. Create your first one.</p></div>
  <?php else: ?>
  <div class="card" style="padding:0">
    <?php foreach ($payslips as $ps):
      $sc = $ps['status']==='issued' ? 'ps-issued' : 'ps-draft'; ?>
    <div class="ps-row-item">
      <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:0">
        <div style="width:36px;height:36px;border-radius:50%;background:var(--orange-bg);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">👤</div>
        <div style="min-width:0">
          <div style="font-size:13px;font-weight:700;color:var(--text)"><?= h($ps['employee_name']) ?></div>
          <div style="font-size:11.5px;color:var(--text3)"><?= h($ps['pay_period']) ?><?= $ps['designation']?' · '.h($ps['designation']):'' ?><?= $ps['company_name']?' · '.h($ps['company_name']):'' ?></div>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:12px;flex-shrink:0">
        <div style="text-align:right">
          <div style="font-size:14px;font-weight:700;color:var(--text)"><?= h($ps['currency']) ?> <?= number_format($ps['net_salary'],2) ?></div>
          <div style="font-size:11px;color:var(--text3)">Net</div>
        </div>
        <span class="ps-status <?= $sc ?>"><?= ucfirst($ps['status']) ?></span>
        <div style="display:flex;gap:6px">
          <a href="payslip.php?view=<?= $ps['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="View">👁</a>
          <a href="payslip.php?export=print&pid=<?= $ps['id'] ?>" target="_blank" class="btn btn-ghost btn-sm btn-icon" title="Print/PDF">🖨</a>
          <a href="payslip.php?export=docx&pid=<?= $ps['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="DOCX">⬇</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- CREATE TAB -->
<div id="tab-create" style="display:<?= $tab==='create'?'block':'none' ?>">
<?php
$edit_id = (int)($_GET['edit'] ?? 0);
$ep = null;
if ($edit_id) {
    $ep = $db->query("SELECT * FROM payslips WHERE id=$edit_id")->fetch_assoc();
}
$ep_allow = $ep ? json_decode($ep['allowances']??'[]',true) : [['name'=>'House Allowance','amount'=>0],['name'=>'Transport','amount'=>0]];
$ep_ded   = $ep ? json_decode($ep['deductions']??'[]',true)  : [['name'=>'EPF (8%)','amount'=>0],['name'=>'ETF (3%)','amount'=>0]];
?>
  <form method="POST" id="payslip-form">
    <input type="hidden" name="action" value="save_payslip">
    <?php if ($ep): ?><input type="hidden" name="payslip_id" value="<?= $ep['id'] ?>"><?php endif; ?>
    <div class="ps-grid">
      <!-- Left: Employee + Pay info -->
      <div>
        <div class="ps-card" style="margin-bottom:16px">
          <div class="ps-card-head"><div class="ps-card-title">👤 Employee</div></div>
          <div class="ps-card-body">
            <div class="form-group">
              <label class="form-label">Employee (CRM User)</label>
              <select name="employee_id" class="form-control" onchange="fillEmployee(this)">
                <option value="">— Custom / Non-CRM —</option>
                <?php foreach ($employees as $e): ?>
                <option value="<?= $e['id'] ?>" <?= ($ep&&$ep['employee_id']==$e['id'])?'selected':'' ?>
                  data-name="<?= h($e['name']) ?>" data-email="<?= h($e['email']) ?>"
                  data-phone="<?= h($e['phone']??'') ?>" data-dept="<?= h($e['department']??'') ?>">
                  <?= h($e['name']) ?><?= $e['department']?' — '.$e['department']:'' ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="employee_name" class="form-control" required value="<?= h($ep['employee_name']??'') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Employee ID</label>
                <input type="text" name="employee_id_no" class="form-control" value="<?= h($ep['employee_id_no']??'') ?>" placeholder="EMP-001">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Designation</label>
                <input type="text" name="designation" class="form-control" value="<?= h($ep['designation']??'') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Department</label>
                <input type="text" name="department" class="form-control" value="<?= h($ep['department']??'') ?>">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="employee_email" class="form-control" value="<?= h($ep['employee_email']??'') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" name="employee_phone" class="form-control" value="<?= h($ep['employee_phone']??'') ?>">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Bank Name</label>
                <input type="text" name="bank_name" class="form-control" value="<?= h($ep['bank_name']??'') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Account Number</label>
                <input type="text" name="account_no" class="form-control" value="<?= h($ep['account_no']??'') ?>">
              </div>
            </div>
          </div>
        </div>

        <div class="ps-card">
          <div class="ps-card-head"><div class="ps-card-title">📅 Pay Period</div></div>
          <div class="ps-card-body">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Pay Period *</label>
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
                  <?php foreach (['LKR','USD','INR','SGD','AED','GBP','EUR'] as $c): ?>
                  <option value="<?= $c ?>" <?= ($ep['currency']??'LKR')===$c?'selected':'' ?>><?= $c ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Template</label>
                <select name="template_id" class="form-control">
                  <option value="">— No template —</option>
                  <?php foreach ($templates as $t): ?>
                  <option value="<?= $t['id'] ?>" <?= ($ep['template_id']??$default_tpl['id']??'')==$t['id']?'selected':'' ?>><?= h($t['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                  <option value="draft" <?= ($ep['status']??'')==='draft'?'selected':'' ?>>Draft</option>
                  <option value="issued" <?= ($ep['status']??'')==='issued'?'selected':'' ?>>Issued</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Notes</label>
              <textarea name="notes" class="form-control" rows="2"><?= h($ep['notes']??'') ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Salary breakdown -->
      <div>
        <div class="ps-card" style="margin-bottom:16px">
          <div class="ps-card-head"><div class="ps-card-title">💰 Earnings</div></div>
          <div class="ps-card-body">
            <div class="form-group">
              <label class="form-label">Basic Salary *</label>
              <input type="number" name="basic_salary" id="basic-salary" class="form-control" step="0.01" min="0" required value="<?= $ep['basic_salary']??'' ?>" oninput="calcTotal()">
            </div>
            <div class="form-group">
              <label class="form-label">Allowances</label>
              <div class="earn-row-builder" id="allow-builder">
                <?php foreach ($ep_allow as $a): ?>
                <div class="row-line">
                  <input type="text"   name="allowance_name[]"   value="<?= h($a['name']) ?>"  placeholder="Allowance name">
                  <input type="number" name="allowance_amount[]" value="<?= $a['amount'] ?>" placeholder="0.00" step="0.01" min="0" oninput="calcTotal()">
                  <button type="button" class="del-row" onclick="delRow(this)">✕</button>
                </div>
                <?php endforeach; ?>
              </div>
              <button type="button" class="add-row-btn" onclick="addRow('allow')">＋ Add Allowance</button>
            </div>
            <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:13px;font-weight:700;border-top:2px solid var(--border);margin-top:8px">
              <span>Gross Salary</span><span id="gross-display">0.00</span>
            </div>
          </div>
        </div>

        <div class="ps-card" style="margin-bottom:16px">
          <div class="ps-card-head"><div class="ps-card-title">📉 Deductions</div></div>
          <div class="ps-card-body">
            <div class="earn-row-builder" id="ded-builder">
              <?php foreach ($ep_ded as $d): ?>
              <div class="row-line">
                <input type="text"   name="deduction_name[]"   value="<?= h($d['name']) ?>"  placeholder="Deduction name">
                <input type="number" name="deduction_amount[]" value="<?= $d['amount'] ?>" placeholder="0.00" step="0.01" min="0" oninput="calcTotal()">
                <button type="button" class="del-row" onclick="delRow(this)">✕</button>
              </div>
              <?php endforeach; ?>
            </div>
            <button type="button" class="add-row-btn" onclick="addRow('ded')">＋ Add Deduction</button>
            <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:13px;font-weight:700;border-top:2px solid var(--border);margin-top:8px">
              <span>Total Deductions</span><span id="ded-display">0.00</span>
            </div>
          </div>
        </div>

        <div class="card" style="padding:16px;background:var(--orange-bg);border-color:var(--orange)">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <div style="font-size:14px;font-weight:700;color:var(--orange)">💵 Net Salary</div>
            <div style="font-size:22px;font-weight:800;color:var(--orange)" id="net-display">0.00</div>
          </div>
        </div>

        <div style="display:flex;gap:10px;margin-top:14px">
          <button type="submit" class="btn btn-primary" style="flex:1">💾 Save Payslip</button>
          <button type="submit" name="status" value="issued" class="btn btn-ghost" style="flex:1">✅ Save & Issue</button>
        </div>
      </div>
    </div>
  </form>
</div>

<!-- TEMPLATES TAB -->
<div id="tab-templates" style="display:<?= isset($_GET['templates'])?'block':'none' ?>">
  <div class="ps-grid">
    <div>
      <div class="ps-card-title" style="margin-bottom:14px">Company Templates</div>
      <?php if (!$templates): ?>
      <div class="empty-state"><div class="icon">📋</div><p>No templates yet.</p></div>
      <?php else: ?>
      <?php foreach ($templates as $t): ?>
      <div class="card" style="margin-bottom:10px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:10px">
        <div>
          <div style="font-weight:700;font-size:13px"><?= h($t['name']) ?> <?= $t['is_default']?'<span style="font-size:10px;background:var(--orange-bg);color:var(--orange);padding:2px 6px;border-radius:99px">Default</span>':'' ?></div>
          <div style="font-size:12px;color:var(--text3)"><?= h($t['company_name']) ?></div>
        </div>
        <div style="display:flex;gap:6px">
          <a href="?templates=1&edit_tpl=<?= $t['id'] ?>" class="btn btn-ghost btn-sm">✎</a>
          <form method="POST" onsubmit="return confirm('Delete template?')" style="display:inline">
            <input type="hidden" name="action" value="delete_template">
            <input type="hidden" name="template_id" value="<?= $t['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm btn-icon">🗑</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div>
      <?php
      $edit_tpl = (int)($_GET['edit_tpl'] ?? 0);
      $et = $edit_tpl ? $db->query("SELECT * FROM payslip_templates WHERE id=$edit_tpl")->fetch_assoc() : null;
      ?>
      <div class="ps-card">
        <div class="ps-card-head"><div class="ps-card-title"><?= $et?'Edit Template':'New Template' ?></div></div>
        <div class="ps-card-body">
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_template">
            <?php if ($et): ?><input type="hidden" name="template_id" value="<?= $et['id'] ?>"><?php endif; ?>
            <div class="form-group">
              <label class="form-label">Template Name *</label>
              <input type="text" name="tpl_name" class="form-control" required value="<?= h($et['name']??'') ?>" placeholder="e.g. Default Company Template">
            </div>
            <div class="form-group">
              <label class="form-label">Company Name *</label>
              <input type="text" name="company_name" class="form-control" required value="<?= h($et['company_name']??'Padak') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Company Address</label>
              <textarea name="company_address" class="form-control" rows="2"><?= h($et['company_address']??'') ?></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Company Logo</label>
              <input type="file" name="company_logo" class="form-control" accept=".png,.jpg,.jpeg">
            </div>
            <div class="form-group">
              <label class="form-label">Footer Note</label>
              <input type="text" name="footer_note" class="form-control" value="<?= h($et['footer_note']??'This is a computer-generated payslip and requires no signature.') ?>">
            </div>
            <div class="form-group">
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="checkbox" name="is_default" value="1" <?= ($et['is_default']??0)?'checked':'' ?>> Set as default template
              </label>
            </div>
            <button type="submit" class="btn btn-primary">Save Template</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
function showTab(t){
    ['list','create','templates'].forEach(function(n){
        var el=document.getElementById('tab-'+n);
        if(el) el.style.display=n===t?'block':'none';
    });
    document.querySelectorAll('.ps-tab').forEach(function(b,i){
        b.classList.toggle('active',['list','create','templates'][i]===t);
    });
}

function fillEmployee(sel){
    var opt=sel.options[sel.selectedIndex];
    if(!opt||!opt.value)return;
    document.querySelector('[name=employee_name]').value  = opt.dataset.name||'';
    document.querySelector('[name=employee_email]').value = opt.dataset.email||'';
    document.querySelector('[name=employee_phone]').value = opt.dataset.phone||'';
    document.querySelector('[name=department]').value     = opt.dataset.dept||'';
}

function addRow(type){
    var builder=document.getElementById(type==='allow'?'allow-builder':'ded-builder');
    var prefix =type==='allow'?'allowance':'deduction';
    var row=document.createElement('div');row.className='row-line';
    row.innerHTML='<input type="text" name="'+prefix+'_name[]" placeholder="Name">'
        +'<input type="number" name="'+prefix+'_amount[]" placeholder="0.00" step="0.01" min="0" oninput="calcTotal()">'
        +'<button type="button" class="del-row" onclick="delRow(this)">✕</button>';
    builder.appendChild(row);
}

function delRow(btn){btn.closest('.row-line').remove();calcTotal();}

function calcTotal(){
    var basic=parseFloat(document.getElementById('basic-salary')?.value)||0;
    var allow=0;
    document.querySelectorAll('[name="allowance_amount[]"]').forEach(function(i){allow+=parseFloat(i.value)||0;});
    var ded=0;
    document.querySelectorAll('[name="deduction_amount[]"]').forEach(function(i){ded+=parseFloat(i.value)||0;});
    var gross=basic+allow; var net=gross-ded;
    var fmt=function(n){return n.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});};
    var gd=document.getElementById('gross-display');if(gd)gd.textContent=fmt(gross);
    var dd=document.getElementById('ded-display');if(dd)dd.textContent=fmt(ded);
    var nd=document.getElementById('net-display');if(nd)nd.textContent=fmt(net);
}

document.addEventListener('DOMContentLoaded',calcTotal);
</script>
<?php renderLayoutEnd(); ?>