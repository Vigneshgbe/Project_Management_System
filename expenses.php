<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
requireRole(['admin','manager']); // Members cannot access finances
$db = getCRMDB();
$user = currentUser();

// ── CONSTANTS ──
$CATEGORIES = [
    'Office & Rent','Software & Tools','Marketing',
    'Legal & Registration','Company Branding','Miscellaneous',
    'Internet & WiFi','Employee Salary','Daily Expenses','Other'
];
$CAT_ICONS = [
    'Office & Rent'=>'🏢','Software & Tools'=>'💻','Marketing'=>'📣',
    'Legal & Registration'=>'📜','Company Branding'=>'🎨','Miscellaneous'=>'🔮',
    'Internet & WiFi'=>'🌐','Employee Salary'=>'💰','Daily Expenses'=>'☕','Other'=>'📌'
];

// ── POST HANDLERS ──
ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Create/edit month
    if ($action === 'save_month') {
        $mid   = (int)($_POST['month_id'] ?? 0);
        $my    = trim($_POST['month_year'] ?? '');
        $label = trim($_POST['month_label'] ?? '');
        $rev   = (float)($_POST['revenue'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $uid   = $user['id'];
        if (!$my) { flash('Month required.','error'); ob_end_clean(); header('Location: expenses.php'); exit; }
        if (!$label) $label = date('F Y', strtotime($my.'-01'));
        if ($mid) {
            $stmt = $db->prepare("UPDATE expense_months SET month_year=?,month_label=?,revenue=?,notes=? WHERE id=?");
            $stmt->bind_param("ssdsi",$my,$label,$rev,$notes,$mid);
            $stmt->execute();
            flash('Month updated.','success');
        } else {
            // Check exists
            $exists = $db->query("SELECT id FROM expense_months WHERE month_year='".$db->real_escape_string($my)."'")->fetch_assoc();
            if ($exists) { flash('That month already exists.','error'); ob_end_clean(); header('Location: expenses.php'); exit; }
            $stmt = $db->prepare("INSERT INTO expense_months (month_year,month_label,revenue,notes,created_by) VALUES (?,?,?,?,?)");
            $stmt->bind_param("ssdsi",$my,$label,$rev,$notes,$uid);
            $stmt->execute();
            $mid = $db->insert_id;
            // Auto-insert all categories
            $scat = $db->prepare("INSERT INTO expense_entries (month_id,category,description,own_spend,office_spend,currency,created_by) VALUES (?,?,?,0,0,'INR',?)");
            $descs = [
                'Office & Rent'=>'Rented space cost','Software & Tools'=>'Claude, Canva, AI Bots',
                'Marketing'=>'Campaigns, Managing','Legal & Registration'=>'Office register Docs',
                'Company Branding'=>'Own Brand Website','Miscellaneous'=>'In case of something',
                'Internet & WiFi'=>'Internet usage','Employee Salary'=>'Salary to Workers',
                'Daily Expenses'=>'Tea, Snacks, Gifts','Other'=>''
            ];
            foreach ($CATEGORIES as $cat) {
                $desc = $descs[$cat] ?? '';
                $scat->bind_param("issi",$mid,$cat,$desc,$uid);
                $scat->execute();
            }
            logActivity('created expense month',$label,$mid);
            flash('Month created with all categories.','success');
        }
        ob_end_clean(); header('Location: expenses.php?month='.$mid); exit;
    }

    // Save individual entry
    if ($action === 'save_entry') {
        $eid     = (int)($_POST['entry_id'] ?? 0);
        $mid     = (int)($_POST['month_id'] ?? 0);
        $cat     = $_POST['category'] ?? 'Other';
        $desc    = trim($_POST['description'] ?? '');
        $own     = (float)($_POST['own_spend'] ?? 0);
        $office  = (float)($_POST['office_spend'] ?? 0);
        $cur     = $_POST['currency'] ?? 'INR';
        $pdate   = $_POST['purchase_date'] ?: null;
        $edate   = $_POST['expire_date'] ?: null;
        $notes   = trim($_POST['notes'] ?? '');
        $uid     = $user['id'];
        if ($eid) {
            $stmt = $db->prepare("UPDATE expense_entries SET category=?,description=?,own_spend=?,office_spend=?,currency=?,purchase_date=?,expire_date=?,notes=? WHERE id=?");
            $stmt->bind_param("ssddssssi",$cat,$desc,$own,$office,$cur,$pdate,$edate,$notes,$eid);
            $stmt->execute();
        } else {
            $stmt = $db->prepare("INSERT INTO expense_entries (month_id,category,description,own_spend,office_spend,currency,purchase_date,expire_date,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("issddssssi",$mid,$cat,$desc,$own,$office,$cur,$pdate,$edate,$notes,$uid);
            $stmt->execute();
        }
        flash('Entry saved.','success');
        ob_end_clean(); header('Location: expenses.php?month='.$mid); exit;
    }

    // Inline quick-save (AJAX)
    if ($action === 'quick_save') {
        header('Content-Type: application/json');
        $eid    = (int)($_POST['entry_id'] ?? 0);
        $own    = (float)($_POST['own_spend'] ?? 0);
        $office = (float)($_POST['office_spend'] ?? 0);
        if ($eid) {
            $stmt = $db->prepare("UPDATE expense_entries SET own_spend=?,office_spend=? WHERE id=?");
            $stmt->bind_param("ddi",$own,$office,$eid);
            $stmt->execute();
        }
        ob_end_clean(); echo json_encode(['ok'=>true]); exit;
    }

    // Save revenue for month
    if ($action === 'save_revenue') {
        $mid = (int)$_POST['month_id'];
        $rev = (float)$_POST['revenue'];
        $db->query("UPDATE expense_months SET revenue=$rev WHERE id=$mid");
        flash('Revenue updated.','success');
        ob_end_clean(); header('Location: expenses.php?month='.$mid); exit;
    }

    // Delete month
    if ($action === 'delete_month' && isAdmin()) {
        $mid = (int)$_POST['month_id'];
        $db->query("DELETE FROM expense_months WHERE id=$mid");
        flash('Month deleted.','success');
        ob_end_clean(); header('Location: expenses.php'); exit;
    }

    // Save subscription
    if ($action === 'save_sub') {
        $sid  = (int)($_POST['sub_id'] ?? 0);
        $inv  = trim($_POST['invoice_number'] ?? '');
        $pt   = trim($_POST['paid_to'] ?? '');
        $di   = $_POST['date_of_issue'] ?: null;
        $de   = $_POST['date_of_end'] ?: null;
        $amt  = (float)($_POST['paid_amount'] ?? 0);
        $cur  = $_POST['currency'] ?? 'USD';
        $pm   = trim($_POST['payment_method'] ?? '');
        $st   = $_POST['status'] ?? 'active';
        $pb   = (int)($_POST['paid_by'] ?? 0) ?: null;
        $notes= trim($_POST['notes'] ?? '');
        $uid  = $user['id'];
        if (!$pt) { flash('Paid To required.','error'); ob_end_clean(); header('Location: expenses.php?tab=subscriptions'); exit; }
        if ($sid) {
            $stmt = $db->prepare("UPDATE subscriptions SET invoice_number=?,paid_to=?,date_of_issue=?,date_of_end=?,paid_amount=?,currency=?,payment_method=?,status=?,paid_by=?,notes=? WHERE id=?");
            $stmt->bind_param("ssssdssssii",$inv,$pt,$di,$de,$amt,$cur,$pm,$st,$pb,$notes,$sid);
        } else {
            $stmt = $db->prepare("INSERT INTO subscriptions (invoice_number,paid_to,date_of_issue,date_of_end,paid_amount,currency,payment_method,status,paid_by,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssdssssii",$inv,$pt,$di,$de,$amt,$cur,$pm,$st,$pb,$notes,$uid);
        }
        $stmt->execute();
        flash('Subscription saved.','success');
        ob_end_clean(); header('Location: expenses.php?tab=subscriptions'); exit;
    }
    // Save software purchase
    if ($action === 'save_sw') {
        $swid = (int)($_POST['sw_id'] ?? 0);
        $inv  = trim($_POST['invoice_number'] ?? '');
        $pt   = trim($_POST['paid_to'] ?? '');
        $dp   = $_POST['date_purchase'] ?: null;
        $de   = $_POST['date_expire'] ?: null;
        $ul   = trim($_POST['usage_limit'] ?? '');
        $amt  = (float)($_POST['paid_amount'] ?? 0);
        $cur  = $_POST['currency'] ?? 'INR';
        $pm   = trim($_POST['payment_method'] ?? '');
        $pb   = (int)($_POST['paid_by'] ?? 0) ?: null;
        $notes= trim($_POST['notes'] ?? '');
        $uid  = $user['id'];
        if (!$pt) { flash('Paid To required.','error'); ob_end_clean(); header('Location: expenses.php?tab=software'); exit; }
        if ($swid) {
            $stmt = $db->prepare("UPDATE software_purchases SET invoice_number=?,paid_to=?,date_purchase=?,date_expire=?,usage_limit=?,paid_amount=?,currency=?,payment_method=?,paid_by=?,notes=? WHERE id=?");
            $stmt->bind_param("sssssdsssii",$inv,$pt,$dp,$de,$ul,$amt,$cur,$pm,$pb,$notes,$swid);
        } else {
            $stmt = $db->prepare("INSERT INTO software_purchases (invoice_number,paid_to,date_purchase,date_expire,usage_limit,paid_amount,currency,payment_method,paid_by,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssdsssii",$inv,$pt,$dp,$de,$ul,$amt,$cur,$pm,$pb,$notes,$uid);
        }
        $stmt->execute();
        flash('Purchase saved.','success');
        ob_end_clean(); header('Location: expenses.php?tab=software'); exit;
    }
    // Delete subscription
    if ($action === 'delete_sub') {
        $sid = (int)$_POST['sub_id'];
        $db->query("DELETE FROM subscriptions WHERE id=$sid");
        flash('Deleted.','success');
        ob_end_clean(); header('Location: expenses.php?tab=subscriptions'); exit;
    }
    // Delete software
    if ($action === 'delete_sw') {
        $swid = (int)$_POST['sw_id'];
        $db->query("DELETE FROM software_purchases WHERE id=$swid");
        flash('Deleted.','success');
        ob_end_clean(); header('Location: expenses.php?tab=software'); exit;
    }

    // Delete entry
    if ($action === 'delete_entry') {
        $eid = (int)$_POST['entry_id'];
        $mid = (int)$_POST['month_id'];
        $db->query("DELETE FROM expense_entries WHERE id=$eid");
        flash('Entry deleted.','success');
        ob_end_clean(); header('Location: expenses.php?month='.$mid); exit;
    }
}
ob_end_clean();

// ── TAB: subscriptions or software or main ──
$tab  = $_GET['tab'] ?? 'monthly';
$view_mid = (int)($_GET['month'] ?? 0);

// ── LOAD MONTHS ──
$months = $db->query("
    SELECT m.*, 
        COALESCE(SUM(e.own_spend),0) AS total_own,
        COALESCE(SUM(e.office_spend),0) AS total_office,
        COUNT(e.id) AS entry_count
    FROM expense_months m
    LEFT JOIN expense_entries e ON e.month_id=m.id
    GROUP BY m.id ORDER BY m.month_year DESC
")->fetch_all(MYSQLI_ASSOC);

// If no month selected, pick latest
if (!$view_mid && !empty($months)) $view_mid = $months[0]['id'];

$current_month = null;
$entries = [];
if ($view_mid) {
    $current_month = $db->query("SELECT * FROM expense_months WHERE id=$view_mid")->fetch_assoc();
    if ($current_month) {
        $entries = $db->query("SELECT * FROM expense_entries WHERE month_id=$view_mid ORDER BY FIELD(category,'Office & Rent','Software & Tools','Marketing','Legal & Registration','Company Branding','Miscellaneous','Internet & WiFi','Employee Salary','Daily Expenses','Other'), id")->fetch_all(MYSQLI_ASSOC);
    }
}

// Compute totals for current month
$own_total = 0; $office_total = 0;
foreach ($entries as $e) { $own_total += $e['own_spend']; $office_total += $e['office_spend']; }
$total_spend = $own_total + $office_total;
$revenue = $current_month ? (float)$current_month['revenue'] : 0;
$balance = $revenue - $total_spend;

// ── SUBSCRIPTIONS ──
$subs = $db->query("
    SELECT s.*, u.name AS paid_by_name FROM subscriptions s
    LEFT JOIN users u ON u.id=s.paid_by
    ORDER BY s.date_of_issue DESC
")->fetch_all(MYSQLI_ASSOC);
$sub_total_paid = 0; foreach($subs as $s) $sub_total_paid += $s['paid_amount'];

// ── SOFTWARE PURCHASES ──
$software = $db->query("
    SELECT sp.*, u.name AS paid_by_name FROM software_purchases sp
    LEFT JOIN users u ON u.id=sp.paid_by
    ORDER BY sp.date_purchase DESC
")->fetch_all(MYSQLI_ASSOC);

// All users for paid_by dropdowns
$all_users = $db->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Overall summary across all months
$overall = $db->query("
    SELECT 
        COALESCE(SUM(e.own_spend),0) AS total_own,
        COALESCE(SUM(e.office_spend),0) AS total_office,
        COALESCE(SUM(m.revenue),0) AS total_revenue
    FROM expense_months m
    LEFT JOIN expense_entries e ON e.month_id=m.id
")->fetch_assoc();
$overall_balance = $overall['total_revenue'] - $overall['total_own'] - $overall['total_office'];

renderLayout('Expenses', 'expenses');
?>

<style>
.exp-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:20px}
.exp-tab{padding:10px 20px;font-size:13px;font-weight:600;color:var(--text3);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s;text-decoration:none}
.exp-tab.active,.exp-tab:hover{color:var(--orange);border-bottom-color:var(--orange)}
.month-list{display:flex;flex-direction:column;gap:6px}
.month-pill{padding:9px 12px;border-radius:var(--radius-sm);background:var(--bg3);border:1px solid var(--border);cursor:pointer;font-size:13px;font-weight:500;color:var(--text2);transition:all .15s;display:flex;justify-content:space-between;align-items:center}
.month-pill:hover,.month-pill.active{background:var(--orange-bg);border-color:var(--orange);color:var(--orange)}
.exp-table{width:100%;border-collapse:collapse}
.exp-table th{background:var(--bg3);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);padding:9px 12px;border:1px solid var(--border);white-space:nowrap;text-align:center}
.exp-table th:nth-child(1){text-align:left;width:30px}
.exp-table th:nth-child(2),.exp-table th:nth-child(3){text-align:left}
.exp-table td{padding:8px 12px;border:1px solid var(--border);font-size:13px;color:var(--text2);vertical-align:middle}
.exp-table tr:hover td{background:var(--tr-hover)}
.exp-table .td-cat{font-weight:600;color:var(--text);white-space:nowrap}
.exp-input{width:100%;background:transparent;border:none;color:var(--text);font-size:13px;text-align:right;padding:2px 4px;border-radius:4px;transition:background .15s}
.exp-input:focus{outline:none;background:var(--bg3);border:1px solid var(--orange)}
.total-row td{background:var(--bg3);font-weight:700;color:var(--text);border-top:2px solid var(--border2)}
.balance-pos{color:var(--green);font-weight:700}
.balance-neg{color:var(--red);font-weight:700}
.summary-band{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin-bottom:18px}
.s-box{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:14px;text-align:center}
.s-box-val{font-family:var(--font-display);font-size:20px;font-weight:700}
.s-box-lbl{font-size:11px;color:var(--text3);margin-top:3px}
@media(max-width:900px){.exp-layout{flex-direction:column}.month-sidebar{width:100%!important;min-width:unset!important}}
</style>

<!-- TABS -->
<div class="exp-tabs">
  <a href="expenses.php?tab=monthly<?= $view_mid?"&month=$view_mid":'' ?>" class="exp-tab <?= $tab==='monthly'?'active':'' ?>">📅 Monthly Tracker</a>
  <a href="expenses.php?tab=subscriptions" class="exp-tab <?= $tab==='subscriptions'?'active':'' ?>">🔄 Subscriptions</a>
  <a href="expenses.php?tab=software" class="exp-tab <?= $tab==='software'?'active':'' ?>">💻 Software Purchases</a>
  <a href="expenses.php?tab=overview" class="exp-tab <?= $tab==='overview'?'active':'' ?>">📊 Overall Summary</a>
</div>

<?php if ($tab === 'monthly'): // ══════════════════════════ MONTHLY TAB ?>

<div style="display:flex;gap:18px;align-items:flex-start" class="exp-layout">

  <!-- Sidebar: month list -->
  <div style="width:220px;min-width:220px;flex-shrink:0">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
      <span style="font-size:12px;font-weight:600;color:var(--text2)">MONTHS</span>
      <button class="btn btn-primary btn-sm" onclick="openModal('modal-month')">＋</button>
    </div>
    <div class="month-list">
      <?php foreach ($months as $m):
        $m_own = $m['total_own']; $m_off = $m['total_office'];
        $m_total = $m_own + $m_off;
      ?>
      <div class="month-pill <?= $view_mid==$m['id']?'active':'' ?>" onclick="location.href='expenses.php?tab=monthly&month=<?= $m['id'] ?>'">
        <span><?= h($m['month_label']) ?></span>
        <span style="font-size:10px;font-weight:700"><?= number_format($m_total,0) ?></span>
      </div>
      <?php endforeach; ?>
      <?php if (empty($months)): ?>
      <div style="font-size:12px;color:var(--text3);padding:10px;text-align:center">No months yet.<br><a href="#" onclick="openModal('modal-month')" style="color:var(--orange)">Create one</a></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Main content -->
  <div style="flex:1;min-width:0">
    <?php if ($current_month): ?>

    <!-- Summary strip -->
    <div class="summary-band">
      <div class="s-box">
        <div class="s-box-val"><?= number_format($own_total,2) ?></div>
        <div class="s-box-lbl">Own Spend</div>
      </div>
      <div class="s-box">
        <div class="s-box-val"><?= number_format($office_total,2) ?></div>
        <div class="s-box-lbl">Office Spend</div>
      </div>
      <div class="s-box">
        <div class="s-box-val"><?= number_format($total_spend,2) ?></div>
        <div class="s-box-lbl">Total Spend</div>
      </div>
      <div class="s-box">
        <div class="s-box-val" style="color:var(--green)"><?= number_format($revenue,2) ?></div>
        <div class="s-box-lbl">Revenue</div>
      </div>
      <div class="s-box">
        <div class="s-box-val <?= $balance>=0?'balance-pos':'balance-neg' ?>"><?= number_format(abs($balance),2) ?></div>
        <div class="s-box-lbl">Balance <?= $balance<0?'(deficit)':'' ?></div>
      </div>
    </div>

    <!-- Month header -->
    <div class="card-header" style="margin-bottom:14px">
      <div>
        <div style="font-family:var(--font-display);font-size:18px;font-weight:700"><?= h($current_month['month_label']) ?></div>
        <div style="font-size:12px;color:var(--text3)"><?= count($entries) ?> entries · <?= date('M Y') === date('M Y',strtotime($current_month['month_year'].'-01')) ? 'Current month' : '' ?></div>
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <!-- Revenue quick edit -->
        <div style="display:flex;align-items:center;gap:6px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:5px 10px">
          <span style="font-size:11px;color:var(--text3)">Revenue:</span>
          <form method="POST" style="display:flex;gap:4px;align-items:center">
            <input type="hidden" name="action" value="save_revenue">
            <input type="hidden" name="month_id" value="<?= $current_month['id'] ?>">
            <input type="number" name="revenue" step="0.01" value="<?= $current_month['revenue'] ?>" class="exp-input" style="width:120px;text-align:left">
            <button type="submit" class="btn btn-primary btn-sm" style="padding:3px 8px">✓</button>
          </form>
        </div>
        <button class="btn btn-ghost btn-sm" onclick="openModal('modal-entry');document.getElementById('em-mid').value=<?= $current_month['id'] ?>;document.getElementById('em-eid').value=''">＋ Row</button>
        <?php if (isAdmin()): ?>
        <div class="dropdown">
          <button class="btn btn-ghost btn-sm" onclick="toggleDropdown('mdd<?= $current_month['id'] ?>')">⋯</button>
          <div class="dropdown-menu" id="mdd<?= $current_month['id'] ?>">
            <a class="dropdown-item" href="#" onclick="editMonth(<?= htmlspecialchars(json_encode($current_month)) ?>)">✎ Edit Month</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item danger" href="#" onclick="if(confirm('Delete entire month and all entries?'))document.getElementById('del-month-form').submit()">🗑 Delete Month</a>
          </div>
          <form id="del-month-form" method="POST" style="display:none"><input type="hidden" name="action" value="delete_month"><input type="hidden" name="month_id" value="<?= $current_month['id'] ?>"></form>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Expense Table -->
    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch">
    <table class="exp-table">
      <thead>
        <tr>
          <th style="text-align:left">S.No</th>
          <th style="text-align:left;min-width:140px">Category</th>
          <th style="text-align:left;min-width:160px">Description</th>
          <th style="min-width:110px">Own Spend</th>
          <th style="min-width:110px">Office Spend</th>
          <th style="min-width:80px">Currency</th>
          <th style="min-width:100px">Purchase Date</th>
          <th style="min-width:100px">Expire Date</th>
          <th style="min-width:50px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $sno=1; foreach ($entries as $e): ?>
        <tr id="row-<?= $e['id'] ?>">
          <td style="text-align:center;color:var(--text3);font-size:12px"><?= $sno++ ?></td>
          <td class="td-cat"><?= $CAT_ICONS[$e['category']] ?? '📌' ?> <?= h($e['category']) ?></td>
          <td style="color:var(--text2);font-size:12.5px"><?= h($e['description'] ?? '') ?></td>
          <td>
            <input type="number" class="exp-input" step="0.01" min="0"
              value="<?= number_format((float)$e['own_spend'],2,'.','') ?>"
              onchange="quickSave(<?= $e['id'] ?>,this.value,null)"
              data-eid="<?= $e['id'] ?>" data-field="own">
          </td>
          <td>
            <input type="number" class="exp-input" step="0.01" min="0"
              value="<?= number_format((float)$e['office_spend'],2,'.','') ?>"
              onchange="quickSave(<?= $e['id'] ?>,null,this.value)"
              data-eid="<?= $e['id'] ?>" data-field="office">
          </td>
          <td style="text-align:center;font-size:12px;color:var(--text3)"><?= h($e['currency']) ?></td>
          <td style="font-size:12px;text-align:center;color:var(--text2)"><?= $e['purchase_date'] ? fDate($e['purchase_date'],'d-m-Y') : '—' ?></td>
          <td style="font-size:12px;text-align:center;color:var(--text2)"><?= $e['expire_date'] ? fDate($e['expire_date'],'d-m-Y') : '—' ?></td>
          <td style="text-align:center">
            <div style="display:flex;gap:4px;justify-content:center">
              <button class="btn btn-ghost btn-sm btn-icon" title="Edit" onclick='openEditEntry(<?= htmlspecialchars(json_encode($e)) ?>)'>✎</button>
              <form method="POST" onsubmit="return confirm('Delete entry?')" style="display:inline">
                <input type="hidden" name="action" value="delete_entry">
                <input type="hidden" name="entry_id" value="<?= $e['id'] ?>">
                <input type="hidden" name="month_id" value="<?= $current_month['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete">✕</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="total-row">
          <td colspan="3" style="text-align:right;font-size:12px;letter-spacing:.05em;color:var(--text3)">OWN TOTAL</td>
          <td style="text-align:right;color:var(--orange);font-size:14px" id="own-total"><?= number_format($own_total,2) ?> INR</td>
          <td colspan="5"></td>
        </tr>
        <tr class="total-row">
          <td colspan="3" style="text-align:right;font-size:12px;letter-spacing:.05em;color:var(--text3)">OFFICE TOTAL</td>
          <td></td>
          <td style="text-align:right;color:var(--blue);font-size:14px" id="office-total"><?= number_format($office_total,2) ?> INR</td>
          <td colspan="4"></td>
        </tr>
        <tr class="total-row" style="background:var(--bg4)">
          <td colspan="3" style="text-align:right;font-size:12px;letter-spacing:.05em">REVENUE</td>
          <td colspan="2"></td>
          <td colspan="2" style="text-align:center;color:var(--green);font-size:14px" id="revenue-disp"><?= number_format($revenue,2) ?> INR</td>
          <td colspan="2"></td>
        </tr>
        <tr class="total-row" style="background:var(--bg4)">
          <td colspan="3" style="text-align:right;font-size:13px;font-weight:800;letter-spacing:.05em">BALANCE</td>
          <td colspan="5" style="text-align:center;font-size:15px;font-weight:800" class="<?= $balance>=0?'balance-pos':'balance-neg' ?>">
            <?= $balance>=0?'+':'-' ?> <?= number_format(abs($balance),2) ?> INR
          </td>
          <td></td>
        </tr>
      </tfoot>
    </table>
    </div>

    <?php else: ?>
    <div class="card"><div class="empty-state"><div class="icon">📊</div><p>Select a month or <a href="#" onclick="openModal('modal-month')" style="color:var(--orange)">create a new month</a>.</p></div></div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tab === 'subscriptions'): // ══════════════════ SUBSCRIPTIONS TAB ?>

<div class="card-header" style="margin-bottom:16px">
  <div>
    <div style="font-family:var(--font-display);font-size:18px;font-weight:700">Subscriptions & Invoices</div>
    <div style="font-size:12px;color:var(--text3)">Recurring services · Total paid: <strong style="color:var(--orange)">$ <?= number_format($sub_total_paid,2) ?></strong></div>
  </div>
  <button class="btn btn-primary" onclick="openModal('modal-sub')">＋ <span>Add Subscription</span></button>
</div>

<?php if (empty($subs)): ?>
<div class="card"><div class="empty-state"><div class="icon">🔄</div><p>No subscriptions yet.</p></div></div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
  <table>
    <thead><tr>
      <th>S.No</th><th>Invoice Number</th><th>Paid To</th><th>Date of Issue</th><th>Date of End</th>
      <th>Paid Amount</th><th>Payment Method</th><th>Total Paid (Cumulative)</th><th>Status</th><th>Paid By</th><th>Actions</th>
    </tr></thead>
    <tbody>
      <?php $running=0; foreach ($subs as $i=>$s):
        $running += $s['paid_amount'];
        $sc = ['active'=>'#10b981','expired'=>'#ef4444','cancelled'=>'#94a3b8'][$s['status']];
        $today = date('Y-m-d');
        $exp_soon = $s['date_of_end'] && $s['date_of_end'] <= date('Y-m-d',strtotime('+7 days')) && $s['status']==='active';
      ?>
      <tr>
        <td style="text-align:center;color:var(--text3)"><?= $i+1 ?></td>
        <td class="td-main" style="font-size:12.5px"><?= h($s['invoice_number'] ?? '—') ?></td>
        <td style="font-weight:600;color:var(--text)"><?= h($s['paid_to']) ?></td>
        <td style="font-size:12.5px"><?= $s['date_of_issue'] ? fDate($s['date_of_issue'],'M j, Y') : '—' ?></td>
        <td style="font-size:12.5px;<?= $exp_soon?'color:var(--red)':'' ?>"><?= $s['date_of_end'] ? fDate($s['date_of_end'],'M j, Y') : '—' ?><?= $exp_soon?' ⚠':'' ?></td>
        <td style="text-align:right;font-weight:600"><?= h($s['currency']) ?> <?= number_format($s['paid_amount'],2) ?></td>
        <td style="font-size:12.5px"><?= h($s['payment_method'] ?? '—') ?></td>
        <td style="text-align:right;color:var(--orange);font-weight:700">$ <?= number_format($running,2) ?></td>
        <td><span class="badge" style="background:<?= $sc ?>20;color:<?= $sc ?>"><?= h($s['status']) ?></span></td>
        <td style="font-size:12.5px"><?= h($s['paid_by_name'] ?? '—') ?></td>
        <td>
          <div style="display:flex;gap:6px">
            <button class="btn btn-ghost btn-sm btn-icon" onclick='openEditSub(<?= htmlspecialchars(json_encode($s)) ?>)'>✎</button>
            <form method="POST" onsubmit="return confirm('Delete?')">
              <input type="hidden" name="action" value="delete_sub">
              <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm btn-icon">✕</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<?php elseif ($tab === 'software'): // ══════════════════════ SOFTWARE PURCHASES TAB ?>

<div class="card-header" style="margin-bottom:16px">
  <div>
    <div style="font-family:var(--font-display);font-size:18px;font-weight:700">Software & Tool Purchases</div>
    <div style="font-size:12px;color:var(--text3)">One-time and limited-use tool purchases</div>
  </div>
  <button class="btn btn-primary" onclick="openModal('modal-sw')">＋ <span>Add Purchase</span></button>
</div>

<?php if (empty($software)): ?>
<div class="card"><div class="empty-state"><div class="icon">💻</div><p>No purchases yet.</p></div></div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
  <table>
    <thead><tr>
      <th>S.No</th><th>Invoice Number</th><th>Paid To</th><th>Date Purchase</th><th>Date of Expire</th>
      <th>Usage Limit</th><th>Paid Amount</th><th>Payment Method</th><th>Paid By</th><th>Notes</th><th>Actions</th>
    </tr></thead>
    <tbody>
      <?php foreach ($software as $i=>$sw):
        $today = date('Y-m-d');
        $expired = $sw['date_expire'] && $sw['date_expire'] < $today;
      ?>
      <tr>
        <td style="text-align:center;color:var(--text3)"><?= $i+1 ?></td>
        <td class="td-main" style="font-size:12.5px"><?= h($sw['invoice_number'] ?? '—') ?></td>
        <td style="font-weight:600;color:var(--text)"><?= h($sw['paid_to']) ?></td>
        <td style="font-size:12.5px"><?= $sw['date_purchase'] ? fDate($sw['date_purchase'],'d-m-Y') : '—' ?></td>
        <td style="font-size:12.5px;<?= $expired?'color:var(--red)':'' ?>"><?= $sw['date_expire'] ? fDate($sw['date_expire'],'d-m-Y') : '—' ?><?= $expired?' (Expired)':'' ?></td>
        <td style="text-align:center;font-size:12px"><?= h($sw['usage_limit'] ?? '—') ?></td>
        <td style="text-align:right;font-weight:600">₹ <?= number_format($sw['paid_amount'],2) ?> <?= h($sw['currency']) ?></td>
        <td style="font-size:12.5px"><?= h($sw['payment_method'] ?? '—') ?></td>
        <td style="font-size:12.5px"><?= h($sw['paid_by_name'] ?? '—') ?></td>
        <td style="font-size:12px;color:var(--text3);max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($sw['notes'] ?? '—') ?></td>
        <td>
          <div style="display:flex;gap:6px">
            <button class="btn btn-ghost btn-sm btn-icon" onclick='openEditSw(<?= htmlspecialchars(json_encode($sw)) ?>)'>✎</button>
            <form method="POST" onsubmit="return confirm('Delete?')">
              <input type="hidden" name="action" value="delete_sw">
              <input type="hidden" name="sw_id" value="<?= $sw['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm btn-icon">✕</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<?php else: // ══════════════════════════════════════════════ OVERVIEW TAB ?>

<div style="font-family:var(--font-display);font-size:18px;font-weight:700;margin-bottom:16px">Overall Expense Summary</div>

<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
  <div class="stat-card"><div class="stat-icon" style="background:rgba(249,115,22,.12)">💸</div><div><div class="stat-val"><?= number_format($overall['total_own'],0) ?></div><div class="stat-lbl">Total Own Spend</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:rgba(99,102,241,.12)">🏢</div><div><div class="stat-val"><?= number_format($overall['total_office'],0) ?></div><div class="stat-lbl">Total Office Spend</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:rgba(16,185,129,.12)">💰</div><div><div class="stat-val"><?= number_format($overall['total_revenue'],0) ?></div><div class="stat-lbl">Total Revenue</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:<?= $overall_balance>=0?'rgba(16,185,129,.12)':'rgba(239,68,68,.12)' ?>">📊</div><div><div class="stat-val <?= $overall_balance>=0?'':'balance-neg' ?>"><?= number_format(abs($overall_balance),0) ?></div><div class="stat-lbl">Net Balance</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:rgba(139,92,246,.12)">🔄</div><div><div class="stat-val"><?= count($subs) ?></div><div class="stat-lbl">Subscriptions</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:rgba(245,158,11,.12)">💻</div><div><div class="stat-val"><?= count($software) ?></div><div class="stat-lbl">Tool Purchases</div></div></div>
</div>

<!-- Month-by-month table -->
<?php if (!empty($months)): ?>
<div class="card">
  <div class="card-title" style="margin-bottom:14px">Month-by-Month Breakdown</div>
  <div class="table-wrap">
  <table>
    <thead><tr>
      <th>S.No</th><th>Month</th><th>Own Spend</th><th>Office Spend</th><th>Total Spend</th><th>Revenue</th><th>Balance</th>
    </tr></thead>
    <tbody>
      <?php foreach (array_reverse($months) as $i=>$m):
        $mt = $m['total_own'] + $m['total_office'];
        $mb = $m['revenue'] - $mt;
      ?>
      <tr>
        <td style="text-align:center;color:var(--text3)"><?= $i+1 ?></td>
        <td class="td-main"><a href="expenses.php?tab=monthly&month=<?= $m['id'] ?>" style="color:var(--orange)"><?= h($m['month_label']) ?></a></td>
        <td style="text-align:right"><?= number_format($m['total_own'],2) ?></td>
        <td style="text-align:right"><?= number_format($m['total_office'],2) ?></td>
        <td style="text-align:right;font-weight:600"><?= number_format($mt,2) ?></td>
        <td style="text-align:right;color:var(--green)"><?= number_format($m['revenue'],2) ?></td>
        <td style="text-align:right;font-weight:700" class="<?= $mb>=0?'balance-pos':'balance-neg' ?>"><?= ($mb>=0?'+':'-').number_format(abs($mb),2) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<?php endif; // end tabs ?>

<!-- ═══════════ MODALS ═══════════ -->

<!-- Add/Edit Month -->
<div class="modal-overlay" id="modal-month">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="month-modal-title">New Month</div>
      <button class="modal-close" onclick="closeModal('modal-month')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="save_month">
      <input type="hidden" name="month_id" id="mm-id" value="">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Month (YYYY-MM) *</label>
            <input type="month" name="month_year" id="mm-my" class="form-control" required value="<?= date('Y-m') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Label</label>
            <input type="text" name="month_label" id="mm-label" class="form-control" placeholder="e.g. January 2026">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Expected Revenue (INR)</label>
          <input type="number" name="revenue" id="mm-rev" step="0.01" class="form-control" value="0">
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" id="mm-notes" class="form-control" style="min-height:60px"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-month')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Month</button>
      </div>
    </form>
  </div>
</div>

<!-- Add/Edit Entry -->
<div class="modal-overlay" id="modal-entry">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="entry-modal-title">Add Expense Entry</div>
      <button class="modal-close" onclick="closeModal('modal-entry')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="save_entry">
      <input type="hidden" name="month_id" id="em-mid" value="<?= $view_mid ?>">
      <input type="hidden" name="entry_id" id="em-eid" value="">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Category</label>
            <select name="category" id="em-cat" class="form-control">
              <?php foreach ($CATEGORIES as $c): ?>
              <option value="<?= $c ?>"><?= $CAT_ICONS[$c]??'' ?> <?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Currency</label>
            <select name="currency" id="em-cur" class="form-control">
              <option>INR</option><option>USD</option><option>EUR</option><option>LKR</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <input type="text" name="description" id="em-desc" class="form-control" placeholder="e.g. Claude, Canva, AI Bots">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Own Spend</label>
            <input type="number" name="own_spend" id="em-own" step="0.01" class="form-control" value="0">
          </div>
          <div class="form-group">
            <label class="form-label">Office Spend</label>
            <input type="number" name="office_spend" id="em-office" step="0.01" class="form-control" value="0">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Purchase Date</label>
            <input type="date" name="purchase_date" id="em-pdate" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Expire Date</label>
            <input type="date" name="expire_date" id="em-edate" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" id="em-notes" class="form-control" style="min-height:60px"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-entry')">Cancel</button>
        <button type="submit" class="btn btn-primary" id="em-submit">Save Entry</button>
      </div>
    </form>
  </div>
</div>

<!-- Add/Edit Subscription -->
<div class="modal-overlay" id="modal-sub">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="sub-modal-title">Add Subscription</div>
      <button class="modal-close" onclick="closeModal('modal-sub')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="save_sub">
      <input type="hidden" name="sub_id" id="sub-id" value="">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Invoice Number</label>
            <input type="text" name="invoice_number" id="sub-inv" class="form-control" placeholder="e.g. OHYLCYIV-0006">
          </div>
          <div class="form-group">
            <label class="form-label">Paid To *</label>
            <input type="text" name="paid_to" id="sub-pt" class="form-control" required placeholder="e.g. Anthropic">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Date of Issue</label>
            <input type="date" name="date_of_issue" id="sub-di" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Date of End</label>
            <input type="date" name="date_of_end" id="sub-de" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Paid Amount</label>
            <input type="number" name="paid_amount" id="sub-amt" step="0.01" class="form-control" value="0">
          </div>
          <div class="form-group">
            <label class="form-label">Currency</label>
            <select name="currency" id="sub-cur" class="form-control">
              <option>USD</option><option>INR</option><option>EUR</option><option>LKR</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Payment Method</label>
            <input type="text" name="payment_method" id="sub-pm" class="form-control" placeholder="e.g. Visa •••• 4584">
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" id="sub-st" class="form-control">
              <option value="active">Active</option>
              <option value="expired">Expired</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Paid By</label>
          <select name="paid_by" id="sub-pb" class="form-control">
            <option value="">— Select —</option>
            <?php foreach ($all_users as $u): ?>
            <option value="<?= $u['id'] ?>"><?= h($u['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" id="sub-notes" class="form-control" style="min-height:60px"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-sub')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Subscription</button>
      </div>
    </form>
  </div>
</div>

<!-- Add/Edit Software Purchase -->
<div class="modal-overlay" id="modal-sw">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="sw-modal-title">Add Software Purchase</div>
      <button class="modal-close" onclick="closeModal('modal-sw')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="save_sw">
      <input type="hidden" name="sw_id" id="sw-id" value="">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Invoice Number</label>
            <input type="text" name="invoice_number" id="sw-inv" class="form-control" placeholder="e.g. 04752-21822961">
          </div>
          <div class="form-group">
            <label class="form-label">Paid To *</label>
            <input type="text" name="paid_to" id="sw-pt" class="form-control" required placeholder="e.g. Canva">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Date Purchase</label>
            <input type="date" name="date_purchase" id="sw-dp" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Date Expire</label>
            <input type="date" name="date_expire" id="sw-de" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Usage Limit</label>
            <input type="text" name="usage_limit" id="sw-ul" class="form-control" placeholder="e.g. 1 Day, 1 Week, Lifetime" list="ul-list">
            <datalist id="ul-list"><option>1 Day</option><option>1 Week</option><option>1 Month</option><option>1 Year</option><option>Lifetime</option></datalist>
          </div>
          <div class="form-group">
            <label class="form-label">Currency</label>
            <select name="currency" id="sw-cur" class="form-control">
              <option>INR</option><option>USD</option><option>EUR</option><option>LKR</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Paid Amount</label>
            <input type="number" name="paid_amount" id="sw-amt" step="0.01" class="form-control" value="0">
          </div>
          <div class="form-group">
            <label class="form-label">Payment Method</label>
            <input type="text" name="payment_method" id="sw-pm" class="form-control" placeholder="e.g. UPI QRCode">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Paid By</label>
          <select name="paid_by" id="sw-pb" class="form-control">
            <option value="">— Select —</option>
            <?php foreach ($all_users as $u): ?>
            <option value="<?= $u['id'] ?>"><?= h($u['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <input type="text" name="notes" id="sw-notes" class="form-control" placeholder="e.g. Social presence edits">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-sw')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Purchase</button>
      </div>
    </form>
  </div>
</div>

<script>
// ── QUICK INLINE SAVE ──
const ownCache={}, officeCache={};
document.querySelectorAll('.exp-input[data-field="own"]').forEach(inp=>{ownCache[inp.dataset.eid]=parseFloat(inp.value)||0});
document.querySelectorAll('.exp-input[data-field="office"]').forEach(inp=>{officeCache[inp.dataset.eid]=parseFloat(inp.value)||0});

function quickSave(eid, own, office){
  if(own!==null) ownCache[eid]=parseFloat(own)||0;
  if(office!==null) officeCache[eid]=parseFloat(office)||0;
  fetch('expenses.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`action=quick_save&entry_id=${eid}&own_spend=${ownCache[eid]||0}&office_spend=${officeCache[eid]||0}`
  }).then(r=>r.json()).then(d=>{if(d.ok)toast('Saved','success')}).catch(()=>{});
}

// ── EDIT MONTH MODAL ──
function editMonth(m){
  document.getElementById('month-modal-title').textContent='Edit Month';
  document.getElementById('mm-id').value=m.id;
  document.getElementById('mm-my').value=m.month_year;
  document.getElementById('mm-label').value=m.month_label;
  document.getElementById('mm-rev').value=m.revenue;
  document.getElementById('mm-notes').value=m.notes||'';
  openModal('modal-month');
}

// ── EDIT ENTRY MODAL ──
function openEditEntry(e){
  document.getElementById('entry-modal-title').textContent='Edit Entry';
  document.getElementById('em-mid').value=e.month_id;
  document.getElementById('em-eid').value=e.id;
  document.getElementById('em-cat').value=e.category;
  document.getElementById('em-cur').value=e.currency||'INR';
  document.getElementById('em-desc').value=e.description||'';
  document.getElementById('em-own').value=e.own_spend||0;
  document.getElementById('em-office').value=e.office_spend||0;
  document.getElementById('em-pdate').value=e.purchase_date||'';
  document.getElementById('em-edate').value=e.expire_date||'';
  document.getElementById('em-notes').value=e.notes||'';
  document.getElementById('em-submit').textContent='Save Changes';
  openModal('modal-entry');
}

// ── EDIT SUBSCRIPTION ──
function openEditSub(s){
  document.getElementById('sub-modal-title').textContent='Edit Subscription';
  document.getElementById('sub-id').value=s.id;
  document.getElementById('sub-inv').value=s.invoice_number||'';
  document.getElementById('sub-pt').value=s.paid_to||'';
  document.getElementById('sub-di').value=s.date_of_issue||'';
  document.getElementById('sub-de').value=s.date_of_end||'';
  document.getElementById('sub-amt').value=s.paid_amount||0;
  document.getElementById('sub-cur').value=s.currency||'USD';
  document.getElementById('sub-pm').value=s.payment_method||'';
  document.getElementById('sub-st').value=s.status||'active';
  document.getElementById('sub-pb').value=s.paid_by||'';
  document.getElementById('sub-notes').value=s.notes||'';
  openModal('modal-sub');
}

// ── EDIT SOFTWARE ──
function openEditSw(s){
  document.getElementById('sw-modal-title').textContent='Edit Purchase';
  document.getElementById('sw-id').value=s.id;
  document.getElementById('sw-inv').value=s.invoice_number||'';
  document.getElementById('sw-pt').value=s.paid_to||'';
  document.getElementById('sw-dp').value=s.date_purchase||'';
  document.getElementById('sw-de').value=s.date_expire||'';
  document.getElementById('sw-ul').value=s.usage_limit||'';
  document.getElementById('sw-cur').value=s.currency||'INR';
  document.getElementById('sw-amt').value=s.paid_amount||0;
  document.getElementById('sw-pm').value=s.payment_method||'';
  document.getElementById('sw-pb').value=s.paid_by||'';
  document.getElementById('sw-notes').value=s.notes||'';
  openModal('modal-sw');
}

// Auto-fill month label from month picker
document.getElementById('mm-my')?.addEventListener('change',function(){
  const d=new Date(this.value+'-01');
  const lbl=d.toLocaleString('en',{month:'long',year:'numeric'});
  document.getElementById('mm-label').value=lbl;
});
</script>

<?php renderLayoutEnd(); ?>