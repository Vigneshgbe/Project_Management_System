<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
requireRole(['admin','manager']); // Members cannot access finances
$db = getCRMDB();
$user = currentUser();

// ── ENSURE month_revenue_entries TABLE EXISTS ──────────────────────────────
@$db->query("CREATE TABLE IF NOT EXISTS month_revenue_entries (
    id            INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
    month_id      INT(11)       NOT NULL,
    project_name  VARCHAR(200)  NOT NULL DEFAULT '',
    client_name   VARCHAR(200)  NOT NULL DEFAULT '',
    payment_type  ENUM('advance','milestone','final','other') NOT NULL DEFAULT 'advance',
    amount        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    currency      VARCHAR(10)   NOT NULL DEFAULT 'INR',
    payment_date  DATE          NULL,
    notes         TEXT          NULL,
    created_by    INT(11)       NOT NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── CONSTANTS ──────────────────────────────────────────────────────────────
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
$PAY_TYPES = ['advance'=>'🟡 Advance','milestone'=>'🔵 Milestone','final'=>'🟢 Final','other'=>'⚪ Other'];
$PAY_COLORS = ['advance'=>'#f59e0b','milestone'=>'#6366f1','final'=>'#10b981','other'=>'#94a3b8'];

// ── HELPER: sync revenue_entries sum → expense_months.revenue ──────────────
function syncMonthRevenue(mysqli $db, int $mid): void {
    $r = @$db->query("SELECT COALESCE(SUM(amount),0) s FROM month_revenue_entries WHERE month_id=$mid");
    $sum = $r ? (float)$r->fetch_assoc()['s'] : 0;
    $db->query("UPDATE expense_months SET revenue=$sum WHERE id=$mid");
}

// ── POST HANDLERS ──────────────────────────────────────────────────────────
ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Create/edit month ──
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
            $exists = $db->query("SELECT id FROM expense_months WHERE month_year='".$db->real_escape_string($my)."'")->fetch_assoc();
            if ($exists) { flash('That month already exists.','error'); ob_end_clean(); header('Location: expenses.php'); exit; }
            $stmt = $db->prepare("INSERT INTO expense_months (month_year,month_label,revenue,notes,created_by) VALUES (?,?,?,?,?)");
            $stmt->bind_param("ssdsi",$my,$label,$rev,$notes,$uid);
            $stmt->execute();
            $mid = $db->insert_id;
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

    // ── Save individual expense entry ──
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

    // ── Inline quick-save (AJAX) ──
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

    // ── Save revenue for month (manual override — kept for compatibility) ──
    if ($action === 'save_revenue') {
        $mid = (int)$_POST['month_id'];
        $rev = (float)$_POST['revenue'];
        $db->query("UPDATE expense_months SET revenue=$rev WHERE id=$mid");
        flash('Revenue updated.','success');
        ob_end_clean(); header('Location: expenses.php?month='.$mid); exit;
    }

    // ── Save revenue entry (NEW) ──
    if ($action === 'save_rev_entry') {
        $reid  = (int)($_POST['rev_entry_id'] ?? 0);
        $mid   = (int)($_POST['month_id'] ?? 0);
        $proj  = trim($_POST['project_name'] ?? '');
        $cli   = trim($_POST['client_name'] ?? '');
        $ptype = in_array($_POST['payment_type']??'',['advance','milestone','final','other']) ? $_POST['payment_type'] : 'advance';
        $amt   = (float)($_POST['amount'] ?? 0);
        $cur   = $_POST['currency'] ?? 'INR';
        $pdate = $_POST['payment_date'] ?: null;
        $notes = trim($_POST['notes'] ?? '');
        $uid   = $user['id'];
        if (!$proj) { flash('Project name required.','error'); ob_end_clean(); header('Location: expenses.php?month='.$mid); exit; }
        if ($reid) {
            $stmt = $db->prepare("UPDATE month_revenue_entries SET project_name=?,client_name=?,payment_type=?,amount=?,currency=?,payment_date=?,notes=? WHERE id=?");
            $stmt->bind_param("sssdsssi",$proj,$cli,$ptype,$amt,$cur,$pdate,$notes,$reid);
            $stmt->execute();
        } else {
            $stmt = $db->prepare("INSERT INTO month_revenue_entries (month_id,project_name,client_name,payment_type,amount,currency,payment_date,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("isssdsssi",$mid,$proj,$cli,$ptype,$amt,$cur,$pdate,$notes,$uid);
            $stmt->execute();
        }
        syncMonthRevenue($db, $mid);
        flash('Revenue entry saved.','success');
        ob_end_clean(); header('Location: expenses.php?month='.$mid.'&rtab=1'); exit;
    }

    // ── Delete revenue entry (NEW) ──
    if ($action === 'delete_rev_entry') {
        $reid = (int)$_POST['rev_entry_id'];
        $mid  = (int)$_POST['month_id'];
        $db->query("DELETE FROM month_revenue_entries WHERE id=$reid");
        syncMonthRevenue($db, $mid);
        flash('Revenue entry deleted.','success');
        ob_end_clean(); header('Location: expenses.php?month='.$mid.'&rtab=1'); exit;
    }

    // ── Delete month ──
    if ($action === 'delete_month' && isAdmin()) {
        $mid = (int)$_POST['month_id'];
        $db->query("DELETE FROM expense_months WHERE id=$mid");
        $db->query("DELETE FROM month_revenue_entries WHERE month_id=$mid");
        flash('Month deleted.','success');
        ob_end_clean(); header('Location: expenses.php'); exit;
    }

    // ── Save subscription ──
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

    // ── Save software purchase ──
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

    // ── Delete subscription ──
    if ($action === 'delete_sub') {
        $sid = (int)$_POST['sub_id'];
        $db->query("DELETE FROM subscriptions WHERE id=$sid");
        flash('Deleted.','success');
        ob_end_clean(); header('Location: expenses.php?tab=subscriptions'); exit;
    }

    // ── Delete software ──
    if ($action === 'delete_sw') {
        $swid = (int)$_POST['sw_id'];
        $db->query("DELETE FROM software_purchases WHERE id=$swid");
        flash('Deleted.','success');
        ob_end_clean(); header('Location: expenses.php?tab=software'); exit;
    }

    // ── Delete entry ──
    if ($action === 'delete_entry') {
        $eid = (int)$_POST['entry_id'];
        $mid = (int)$_POST['month_id'];
        $db->query("DELETE FROM expense_entries WHERE id=$eid");
        flash('Entry deleted.','success');
        ob_end_clean(); header('Location: expenses.php?month='.$mid); exit;
    }
}
ob_end_clean();

// ── TAB / VIEW ────────────────────────────────────────────────────────────
$tab      = $_GET['tab'] ?? 'monthly';
$view_mid = (int)($_GET['month'] ?? 0);
$show_rtab = isset($_GET['rtab']); // open revenue sub-tab after save

// ── LOAD MONTHS ───────────────────────────────────────────────────────────
$months = $db->query("
    SELECT m.*,
        COALESCE(SUM(e.own_spend),0)    AS total_own,
        COALESCE(SUM(e.office_spend),0) AS total_office,
        COUNT(e.id) AS entry_count
    FROM expense_months m
    LEFT JOIN expense_entries e ON e.month_id=m.id
    GROUP BY m.id ORDER BY m.month_year DESC
")->fetch_all(MYSQLI_ASSOC);

if (!$view_mid && !empty($months)) $view_mid = $months[0]['id'];

$current_month = null;
$entries       = [];
$rev_entries   = [];
if ($view_mid) {
    $current_month = $db->query("SELECT * FROM expense_months WHERE id=$view_mid")->fetch_assoc();
    if ($current_month) {
        $entries = $db->query("SELECT * FROM expense_entries WHERE month_id=$view_mid ORDER BY FIELD(category,'Office & Rent','Software & Tools','Marketing','Legal & Registration','Company Branding','Miscellaneous','Internet & WiFi','Employee Salary','Daily Expenses','Other'), id")->fetch_all(MYSQLI_ASSOC);
        $rev_entries = $db->query("SELECT * FROM month_revenue_entries WHERE month_id=$view_mid ORDER BY payment_date ASC, id ASC")->fetch_all(MYSQLI_ASSOC);
    }
}

// Compute expense totals
$own_total = 0; $office_total = 0;
foreach ($entries as $e) { $own_total += $e['own_spend']; $office_total += $e['office_spend']; }
$total_spend = $own_total + $office_total;

// Compute revenue from entries (project payments)
$revenue_from_entries = 0;
foreach ($rev_entries as $r) { $revenue_from_entries += $r['amount']; }
// If revenue entries exist, use their sum; else fallback to manual revenue field
$revenue = $revenue_from_entries > 0 ? $revenue_from_entries : (float)($current_month['revenue'] ?? 0);
$balance = $revenue - $total_spend;

// Revenue breakdown by payment type
$rev_by_type = ['advance'=>0,'milestone'=>0,'final'=>0,'other'=>0];
foreach ($rev_entries as $r) { $rev_by_type[$r['payment_type']] += $r['amount']; }

// ── SUBSCRIPTIONS ─────────────────────────────────────────────────────────
$subs = $db->query("SELECT s.*, u.name AS paid_by_name FROM subscriptions s LEFT JOIN users u ON u.id=s.paid_by ORDER BY s.date_of_issue DESC")->fetch_all(MYSQLI_ASSOC);
$sub_total_paid = 0; foreach($subs as $s) $sub_total_paid += $s['paid_amount'];

// ── SOFTWARE PURCHASES ────────────────────────────────────────────────────
$software = $db->query("SELECT sp.*, u.name AS paid_by_name FROM software_purchases sp LEFT JOIN users u ON u.id=sp.paid_by ORDER BY sp.date_purchase DESC")->fetch_all(MYSQLI_ASSOC);

// All users for paid_by dropdowns
$all_users = $db->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Overall summary across all months
$overall = $db->query("SELECT COALESCE(SUM(e.own_spend),0) AS total_own, COALESCE(SUM(e.office_spend),0) AS total_office, COALESCE(SUM(m.revenue),0) AS total_revenue FROM expense_months m LEFT JOIN expense_entries e ON e.month_id=m.id")->fetch_assoc();
$overall_balance = $overall['total_revenue'] - $overall['total_own'] - $overall['total_office'];

renderLayout('Expenses', 'expenses');
?>

<style>
/* ── EXISTING STYLES (UNCHANGED) ── */
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

/* ── REVENUE TRACKER STYLES ── */
.rev-subtabs{display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:14px}
.rev-subtab{padding:7px 16px;font-size:12.5px;font-weight:600;color:var(--text3);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px;transition:color .15s,border-color .15s;background:none;border-top:none;border-left:none;border-right:none}
.rev-subtab.active{color:var(--orange);border-bottom-color:var(--orange)}
.rev-tracker-wrap{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px;margin-bottom:16px}
.rev-type-pill{display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:99px;font-size:11px;font-weight:700;white-space:nowrap}
.rev-summary-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:8px;margin-bottom:14px}
.rev-sum-box{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px 12px;text-align:center}
.rev-sum-val{font-size:16px;font-weight:800;font-family:var(--font-display)}
.rev-sum-lbl{font-size:10px;color:var(--text3);margin-top:2px;text-transform:uppercase;letter-spacing:.04em}

/* ── REVENUE TABLE: fixed-layout with controlled column widths ── */
.rev-tbl{width:100%;border-collapse:collapse;font-size:13px;table-layout:fixed}
.rev-tbl th{background:var(--bg3);font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);padding:8px 10px;border-bottom:2px solid var(--border);text-align:left;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rev-tbl td{padding:9px 10px;border-bottom:1px solid var(--border);color:var(--text2);vertical-align:middle;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rev-tbl tr:last-child td{border-bottom:none}
.rev-tbl tr:hover td{background:var(--bg3)}
/* Column widths: #, Project, Client, Type, Amount, Date, Notes, Actions */
.rev-tbl th:nth-child(1),.rev-tbl td:nth-child(1){width:36px;text-align:center;color:var(--text3)}
.rev-tbl th:nth-child(2),.rev-tbl td:nth-child(2){width:18%}
.rev-tbl th:nth-child(3),.rev-tbl td:nth-child(3){width:16%}
.rev-tbl th:nth-child(4),.rev-tbl td:nth-child(4){width:120px}
.rev-tbl th:nth-child(5),.rev-tbl td:nth-child(5){width:140px;text-align:right}
.rev-tbl th:nth-child(6),.rev-tbl td:nth-child(6){width:105px}
.rev-tbl th:nth-child(7),.rev-tbl td:nth-child(7){width:auto}
.rev-tbl th:nth-child(8),.rev-tbl td:nth-child(8){width:72px;text-align:center}

/* ── FIX: Overview Month-by-Month table alignment ── */
.overview-breakdown-tbl{width:100%;border-collapse:collapse;table-layout:fixed}
.overview-breakdown-tbl th{background:var(--bg3);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);padding:9px 12px;border-bottom:2px solid var(--border);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.overview-breakdown-tbl th:nth-child(1){width:60px;text-align:center}
.overview-breakdown-tbl th:nth-child(2){width:auto;text-align:left}
.overview-breakdown-tbl th:nth-child(3),.overview-breakdown-tbl th:nth-child(4),.overview-breakdown-tbl th:nth-child(5),.overview-breakdown-tbl th:nth-child(6),.overview-breakdown-tbl th:nth-child(7){width:14%;text-align:right}
.overview-breakdown-tbl td{padding:9px 12px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text2);vertical-align:middle;overflow:hidden;text-overflow:ellipsis}
.overview-breakdown-tbl tr:last-child td{border-bottom:none}
.overview-breakdown-tbl tr:hover td{background:var(--bg3)}
.overview-breakdown-tbl td:nth-child(1){text-align:center;color:var(--text3)}
.overview-breakdown-tbl td:nth-child(3),.overview-breakdown-tbl td:nth-child(4),.overview-breakdown-tbl td:nth-child(5){text-align:right}
.overview-breakdown-tbl td:nth-child(6){text-align:right;color:var(--green);font-weight:600}
.overview-breakdown-tbl td:nth-child(7){text-align:right;font-weight:700}
</style>

<!-- TABS (UNCHANGED) -->
<div class="exp-tabs">
  <a href="expenses.php?tab=monthly<?= $view_mid?"&month=$view_mid":'' ?>" class="exp-tab <?= $tab==='monthly'?'active':'' ?>">📅 Monthly Tracker</a>
  <a href="expenses.php?tab=subscriptions" class="exp-tab <?= $tab==='subscriptions'?'active':'' ?>">🔄 Subscriptions</a>
  <a href="expenses.php?tab=software" class="exp-tab <?= $tab==='software'?'active':'' ?>">💻 Software Purchases</a>
  <a href="expenses.php?tab=revenue" class="exp-tab <?= $tab==='revenue'?'active':'' ?>">💰 Revenue Tracker</a>
  <a href="expenses.php?tab=overview" class="exp-tab <?= $tab==='overview'?'active':'' ?>">📊 Overall Summary</a>
</div>

<?php if ($tab === 'monthly'): ?>

<div style="display:flex;gap:18px;align-items:flex-start" class="exp-layout">

  <!-- Sidebar: month list (UNCHANGED) -->
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

    <!-- Summary strip (UNCHANGED) -->
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
        <div class="s-box-lbl">Revenue<?= count($rev_entries)>0?' 💰':'' ?></div>
      </div>
      <div class="s-box">
        <div class="s-box-val <?= $balance>=0?'balance-pos':'balance-neg' ?>"><?= number_format(abs($balance),2) ?></div>
        <div class="s-box-lbl">Balance <?= $balance<0?'(deficit)':'' ?></div>
      </div>
    </div>

    <!-- Month header (UNCHANGED) -->
    <div class="card-header" style="margin-bottom:14px">
      <div>
        <div style="font-family:var(--font-display);font-size:18px;font-weight:700"><?= h($current_month['month_label']) ?></div>
        <div style="font-size:12px;color:var(--text3)"><?= count($entries) ?> entries · <?= date('M Y') === date('M Y',strtotime($current_month['month_year'].'-01')) ? 'Current month' : '' ?></div>
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <!-- Revenue quick edit kept for backward compat when no rev entries -->
        <?php if (empty($rev_entries)): ?>
        <div style="display:flex;align-items:center;gap:6px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:5px 10px">
          <span style="font-size:11px;color:var(--text3)">Revenue:</span>
          <form method="POST" style="display:flex;gap:4px;align-items:center">
            <input type="hidden" name="action" value="save_revenue">
            <input type="hidden" name="month_id" value="<?= $current_month['id'] ?>">
            <input type="number" name="revenue" step="0.01" value="<?= $current_month['revenue'] ?>" class="exp-input" style="width:120px;text-align:left">
            <button type="submit" class="btn btn-primary btn-sm" style="padding:3px 8px">✓</button>
          </form>
        </div>
        <?php else: ?>
        <div style="background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.25);border-radius:var(--radius-sm);padding:5px 12px;font-size:12px;color:#10b981;font-weight:600">
          💰 Revenue: <?= number_format($revenue,2) ?> INR (<?= count($rev_entries) ?> payment<?= count($rev_entries)>1?'s':'' ?>)
        </div>
        <?php endif; ?>
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

    <!-- SUB-TABS: Expenses | Revenue Tracker -->
    <div class="rev-subtabs" id="monthly-subtabs">
      <button class="rev-subtab <?= !$show_rtab?'active':'' ?>" onclick="switchMonthTab('expenses')">📋 Expense Entries</button>
      <button class="rev-subtab <?= $show_rtab?'active':'' ?>" onclick="switchMonthTab('revenue')">💰 Revenue Tracker <span id="rev-count-badge" style="background:rgba(16,185,129,.12);color:#10b981;border-radius:99px;padding:1px 7px;font-size:11px;margin-left:4px"><?= count($rev_entries) ?></span></button>
    </div>

    <!-- ══ EXPENSES SUB-TAB (UNCHANGED content) ══ -->
    <div id="mtab-expenses" style="display:<?= $show_rtab?'none':'block' ?>">
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
    </div><!-- end mtab-expenses -->

    <!-- ══ REVENUE TRACKER SUB-TAB ══ -->
    <div id="mtab-revenue" style="display:<?= $show_rtab?'block':'none' ?>">
      <div class="rev-tracker-wrap">

        <!-- Revenue summary boxes -->
        <?php
          $rev_total = array_sum($rev_by_type);
          $project_names = array_unique(array_column($rev_entries,'project_name'));
        ?>
        <div class="rev-summary-row">
          <div class="rev-sum-box">
            <div class="rev-sum-val" style="color:var(--green)"><?= number_format($rev_total,2) ?></div>
            <div class="rev-sum-lbl">Total Revenue</div>
          </div>
          <div class="rev-sum-box">
            <div class="rev-sum-val" style="color:#f59e0b"><?= number_format($rev_by_type['advance'],2) ?></div>
            <div class="rev-sum-lbl">🟡 Advance</div>
          </div>
          <div class="rev-sum-box">
            <div class="rev-sum-val" style="color:#6366f1"><?= number_format($rev_by_type['milestone'],2) ?></div>
            <div class="rev-sum-lbl">🔵 Milestone</div>
          </div>
          <div class="rev-sum-box">
            <div class="rev-sum-val" style="color:#10b981"><?= number_format($rev_by_type['final'],2) ?></div>
            <div class="rev-sum-lbl">🟢 Final Pay</div>
          </div>
          <div class="rev-sum-box">
            <div class="rev-sum-val" style="color:var(--text3)"><?= number_format($rev_by_type['other'],2) ?></div>
            <div class="rev-sum-lbl">⚪ Other</div>
          </div>
          <div class="rev-sum-box">
            <div class="rev-sum-val" style="color:var(--orange)"><?= count($project_names) ?></div>
            <div class="rev-sum-lbl">Projects</div>
          </div>
        </div>

        <!-- Revenue entries table header + add button -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <div style="font-size:13px;font-weight:700;color:var(--text)">Payment Entries <span style="font-size:11px;color:var(--text3);font-weight:400">(<?= count($rev_entries) ?> records)</span></div>
          <button class="btn btn-primary btn-sm" onclick="openRevModal()">＋ Add Payment</button>
        </div>

        <!-- Revenue entries table -->
        <?php if (empty($rev_entries)): ?>
        <div style="text-align:center;padding:28px;color:var(--text3)">
          <div style="font-size:28px;margin-bottom:8px">💰</div>
          <div style="font-size:13px">No revenue entries yet.</div>
          <div style="font-size:12px;margin-top:4px">Track partial project payments — advance, milestone &amp; final.</div>
          <button class="btn btn-primary btn-sm" style="margin-top:12px" onclick="openRevModal()">＋ Add First Payment</button>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table class="rev-tbl">
          <thead>
            <tr>
              <th>#</th>
              <th>Project</th>
              <th>Client</th>
              <th>Type</th>
              <th style="text-align:right">Amount</th>
              <th>Date</th>
              <th>Notes</th>
              <th style="text-align:center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rev_entries as $i=>$r):
              $tc = $PAY_COLORS[$r['payment_type']] ?? '#94a3b8';
            ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td style="font-weight:600;color:var(--text)" title="<?= h($r['project_name']) ?>"><?= h($r['project_name']) ?></td>
              <td style="font-size:12.5px" title="<?= h($r['client_name']) ?>"><?= h($r['client_name'] ?: '—') ?></td>
              <td>
                <span class="rev-type-pill" style="background:<?= $tc ?>18;color:<?= $tc ?>;border:1px solid <?= $tc ?>35">
                  <?= $PAY_TYPES[$r['payment_type']] ?? $r['payment_type'] ?>
                </span>
              </td>
              <td style="text-align:right;font-weight:700;color:var(--green);white-space:nowrap"><?= number_format($r['amount'],2) ?> <?= h($r['currency']) ?></td>
              <td style="font-size:12px;color:var(--text2);white-space:nowrap"><?= $r['payment_date'] ? fDate($r['payment_date'],'d-m-Y') : '—' ?></td>
              <td style="font-size:12px;color:var(--text3)" title="<?= h($r['notes']) ?>"><?= h($r['notes'] ?: '—') ?></td>
              <td>
                <div style="display:flex;gap:4px;justify-content:center">
                  <button class="btn btn-ghost btn-sm btn-icon" onclick='openEditRevModal(<?= htmlspecialchars(json_encode($r)) ?>)' title="Edit">✎</button>
                  <form method="POST" onsubmit="return confirm('Delete this payment entry?')" style="display:inline">
                    <input type="hidden" name="action" value="delete_rev_entry">
                    <input type="hidden" name="rev_entry_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="month_id" value="<?= $current_month['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete">✕</button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr style="background:var(--bg3)">
              <td colspan="4" style="text-align:right;font-size:12px;font-weight:700;color:var(--text3);letter-spacing:.04em;padding:10px">TOTAL REVENUE</td>
              <td style="text-align:right;font-weight:800;font-size:15px;color:var(--green);padding:10px;white-space:nowrap"><?= number_format($rev_total,2) ?> INR</td>
              <td colspan="3"></td>
            </tr>
          </tfoot>
        </table>
        </div>
        <?php endif; ?>
      </div>
    </div><!-- end mtab-revenue -->

    <?php else: ?>
    <div class="card"><div class="empty-state"><div class="icon">📊</div><p>Select a month or <a href="#" onclick="openModal('modal-month')" style="color:var(--orange)">create a new month</a>.</p></div></div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tab === 'subscriptions'): ?>

<!-- SUBSCRIPTIONS TAB (UNCHANGED) -->
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

<?php elseif ($tab === 'software'): ?>

<!-- SOFTWARE PURCHASES TAB (UNCHANGED) -->
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
        $expired = $sw['date_expire'] && $sw['date_expire'] < date('Y-m-d');
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

<?php elseif ($tab === 'revenue'): ?>

<!-- ══ REVENUE TRACKER — TOP-LEVEL TAB ══ -->
<?php
// Load all months for the sidebar
$rev_month_id = (int)($_GET['month'] ?? 0);
if (!$rev_month_id && !empty($months)) $rev_month_id = $months[0]['id'];
$rev_current_month = null;
$rev_entries_tab = [];
$rev_by_type_tab = ['advance'=>0,'milestone'=>0,'final'=>0,'other'=>0];
if ($rev_month_id) {
    $rev_current_month = $db->query("SELECT * FROM expense_months WHERE id=$rev_month_id")->fetch_assoc();
    if ($rev_current_month) {
        $rev_entries_tab = $db->query("SELECT * FROM month_revenue_entries WHERE month_id=$rev_month_id ORDER BY payment_date ASC, id ASC")->fetch_all(MYSQLI_ASSOC);
        foreach ($rev_entries_tab as $r) $rev_by_type_tab[$r['payment_type']] += $r['amount'];
    }
}
$rev_total_tab = array_sum($rev_by_type_tab);
$rev_project_names_tab = array_unique(array_column($rev_entries_tab,'project_name'));
?>

<div style="display:flex;gap:18px;align-items:flex-start" class="exp-layout">

  <!-- Sidebar: same month list -->
  <div style="width:220px;min-width:220px;flex-shrink:0">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
      <span style="font-size:12px;font-weight:600;color:var(--text2)">MONTHS</span>
    </div>
    <div class="month-list">
      <?php foreach ($months as $m):
        $m_rev = (float)$db->query("SELECT COALESCE(SUM(amount),0) s FROM month_revenue_entries WHERE month_id={$m['id']}")->fetch_assoc()['s'];
      ?>
      <div class="month-pill <?= $rev_month_id==$m['id']?'active':'' ?>"
           onclick="location.href='expenses.php?tab=revenue&month=<?= $m['id'] ?>'">
        <span><?= h($m['month_label']) ?></span>
        <span style="font-size:10px;font-weight:700;color:var(--green)"><?= $m_rev>0?number_format($m_rev,0):'' ?></span>
      </div>
      <?php endforeach; ?>
      <?php if (empty($months)): ?>
      <div style="font-size:12px;color:var(--text3);padding:10px;text-align:center">No months yet.<br><a href="expenses.php?tab=monthly" style="color:var(--orange)">Create one first</a></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Main content -->
  <div style="flex:1;min-width:0">
    <?php if ($rev_current_month): ?>

    <!-- Summary boxes -->
    <div class="rev-summary-row" style="margin-bottom:18px">
      <div class="rev-sum-box">
        <div class="rev-sum-val" style="color:var(--green)"><?= number_format($rev_total_tab,2) ?></div>
        <div class="rev-sum-lbl">Total Revenue</div>
      </div>
      <div class="rev-sum-box">
        <div class="rev-sum-val" style="color:#f59e0b"><?= number_format($rev_by_type_tab['advance'],2) ?></div>
        <div class="rev-sum-lbl">🟡 Advance</div>
      </div>
      <div class="rev-sum-box">
        <div class="rev-sum-val" style="color:#6366f1"><?= number_format($rev_by_type_tab['milestone'],2) ?></div>
        <div class="rev-sum-lbl">🔵 Milestone</div>
      </div>
      <div class="rev-sum-box">
        <div class="rev-sum-val" style="color:#10b981"><?= number_format($rev_by_type_tab['final'],2) ?></div>
        <div class="rev-sum-lbl">🟢 Final Pay</div>
      </div>
      <div class="rev-sum-box">
        <div class="rev-sum-val" style="color:var(--text3)"><?= number_format($rev_by_type_tab['other'],2) ?></div>
        <div class="rev-sum-lbl">⚪ Other</div>
      </div>
      <div class="rev-sum-box">
        <div class="rev-sum-val" style="color:var(--orange)"><?= count($rev_project_names_tab) ?></div>
        <div class="rev-sum-lbl">Projects</div>
      </div>
    </div>

    <!-- Month header -->
    <div class="card-header" style="margin-bottom:14px">
      <div>
        <div style="font-family:var(--font-display);font-size:18px;font-weight:700"><?= h($rev_current_month['month_label']) ?> — Revenue</div>
        <div style="font-size:12px;color:var(--text3)"><?= count($rev_entries_tab) ?> payment record<?= count($rev_entries_tab)!=1?'s':'' ?></div>
      </div>
      <button class="btn btn-primary" onclick="openRevModal()">＋ Add Payment</button>
    </div>

    <!-- Revenue entries table -->
    <div class="rev-tracker-wrap">
      <?php if (empty($rev_entries_tab)): ?>
      <div style="text-align:center;padding:36px;color:var(--text3)">
        <div style="font-size:32px;margin-bottom:8px">💰</div>
        <div style="font-size:13px;font-weight:600">No revenue entries for <?= h($rev_current_month['month_label']) ?></div>
        <div style="font-size:12px;margin-top:4px">Track advance, milestone &amp; final project payments here.</div>
        <button class="btn btn-primary btn-sm" style="margin-top:14px" onclick="openRevModal()">＋ Add First Payment</button>
      </div>
      <?php else: ?>
      <div style="overflow-x:auto">
      <table class="rev-tbl">
        <thead>
          <tr>
            <th>#</th>
            <th>Project</th>
            <th>Client</th>
            <th>Type</th>
            <th style="text-align:right">Amount</th>
            <th>Date</th>
            <th>Notes</th>
            <th style="text-align:center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rev_entries_tab as $i=>$r):
            $tc = $PAY_COLORS[$r['payment_type']] ?? '#94a3b8';
          ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td style="font-weight:600;color:var(--text)" title="<?= h($r['project_name']) ?>"><?= h($r['project_name']) ?></td>
            <td style="font-size:12.5px" title="<?= h($r['client_name']) ?>"><?= h($r['client_name'] ?: '—') ?></td>
            <td>
              <span class="rev-type-pill" style="background:<?= $tc ?>18;color:<?= $tc ?>;border:1px solid <?= $tc ?>35">
                <?= $PAY_TYPES[$r['payment_type']] ?? $r['payment_type'] ?>
              </span>
            </td>
            <td style="text-align:right;font-weight:700;color:var(--green);white-space:nowrap"><?= number_format($r['amount'],2) ?> <?= h($r['currency']) ?></td>
            <td style="font-size:12px;color:var(--text2);white-space:nowrap"><?= $r['payment_date'] ? fDate($r['payment_date'],'d-m-Y') : '—' ?></td>
            <td style="font-size:12px;color:var(--text3)" title="<?= h($r['notes']) ?>"><?= h($r['notes'] ?: '—') ?></td>
            <td>
              <div style="display:flex;gap:4px;justify-content:center">
                <button class="btn btn-ghost btn-sm btn-icon" onclick='openEditRevModal(<?= htmlspecialchars(json_encode($r)) ?>)' title="Edit">✎</button>
                <form method="POST" onsubmit="return confirm('Delete this payment entry?')" style="display:inline">
                  <input type="hidden" name="action" value="delete_rev_entry">
                  <input type="hidden" name="rev_entry_id" value="<?= $r['id'] ?>">
                  <input type="hidden" name="month_id" value="<?= $rev_current_month['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete">✕</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background:var(--bg3)">
            <td colspan="4" style="text-align:right;font-size:12px;font-weight:700;color:var(--text3);letter-spacing:.04em;padding:10px">TOTAL REVENUE</td>
            <td style="text-align:right;font-weight:800;font-size:15px;color:var(--green);padding:10px;white-space:nowrap"><?= number_format($rev_total_tab,2) ?> INR</td>
            <td colspan="3"></td>
          </tr>
        </tfoot>
      </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Hidden form for modal POST: points back to revenue tab -->
    <form id="rev-tab-form" method="POST" style="display:none">
      <input type="hidden" name="action" value="save_rev_entry">
      <input type="hidden" name="month_id" value="<?= $rev_month_id ?>">
    </form>

    <?php else: ?>
    <div class="card"><div class="empty-state"><div class="icon">💰</div><p>Select a month from the sidebar to view revenue.</p></div></div>
    <?php endif; ?>
  </div>
</div>

<?php else: // OVERVIEW TAB ?>

<!-- OVERALL SUMMARY TAB — stats unchanged, table FIXED -->
<div style="font-family:var(--font-display);font-size:18px;font-weight:700;margin-bottom:16px">Overall Expense Summary</div>

<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
  <div class="stat-card"><div class="stat-icon" style="background:rgba(249,115,22,.12)">💸</div><div><div class="stat-val"><?= number_format($overall['total_own'],0) ?></div><div class="stat-lbl">Total Own Spend</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:rgba(99,102,241,.12)">🏢</div><div><div class="stat-val"><?= number_format($overall['total_office'],0) ?></div><div class="stat-lbl">Total Office Spend</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:rgba(16,185,129,.12)">💰</div><div><div class="stat-val"><?= number_format($overall['total_revenue'],0) ?></div><div class="stat-lbl">Total Revenue</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:<?= $overall_balance>=0?'rgba(16,185,129,.12)':'rgba(239,68,68,.12)' ?>">📊</div><div><div class="stat-val <?= $overall_balance>=0?'':'balance-neg' ?>"><?= number_format(abs($overall_balance),0) ?></div><div class="stat-lbl">Net Balance</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:rgba(139,92,246,.12)">🔄</div><div><div class="stat-val"><?= count($subs) ?></div><div class="stat-lbl">Subscriptions</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:rgba(245,158,11,.12)">💻</div><div><div class="stat-val"><?= count($software) ?></div><div class="stat-lbl">Tool Purchases</div></div></div>
</div>

<!-- Month-by-Month Breakdown — FIXED column alignment -->
<?php if (!empty($months)): ?>
<div class="card">
  <div class="card-title" style="margin-bottom:14px">Month-by-Month Breakdown</div>
  <div class="table-wrap" style="overflow-x:auto">
  <table class="overview-breakdown-tbl">
    <thead>
      <tr>
        <th>S.No</th>
        <th>Month</th>
        <th>Own Spend</th>
        <th>Office Spend</th>
        <th>Total Spend</th>
        <th>Revenue</th>
        <th>Balance</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (array_reverse($months) as $i=>$m):
        $mt = $m['total_own'] + $m['total_office'];
        $mb = $m['revenue'] - $mt;
      ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><a href="expenses.php?tab=monthly&month=<?= $m['id'] ?>" style="color:var(--orange);font-weight:600"><?= h($m['month_label']) ?></a></td>
        <td><?= number_format($m['total_own'],2) ?></td>
        <td><?= number_format($m['total_office'],2) ?></td>
        <td style="font-weight:600"><?= number_format($mt,2) ?></td>
        <td><?= number_format($m['revenue'],2) ?></td>
        <td class="<?= $mb>=0?'balance-pos':'balance-neg' ?>"><?= ($mb>=0?'+':'-').number_format(abs($mb),2) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <?php
        $sum_own = array_sum(array_column($months,'total_own'));
        $sum_off = array_sum(array_column($months,'total_office'));
        $sum_rev = array_sum(array_column($months,'revenue'));
        $sum_tot = $sum_own + $sum_off;
        $sum_bal = $sum_rev - $sum_tot;
      ?>
      <tr style="background:var(--bg3);font-weight:700;font-size:13px;border-top:2px solid var(--border)">
        <td></td>
        <td style="color:var(--text3);font-size:11px;text-transform:uppercase;letter-spacing:.05em">TOTALS</td>
        <td><?= number_format($sum_own,2) ?></td>
        <td><?= number_format($sum_off,2) ?></td>
        <td><?= number_format($sum_tot,2) ?></td>
        <td style="color:var(--green)"><?= number_format($sum_rev,2) ?></td>
        <td class="<?= $sum_bal>=0?'balance-pos':'balance-neg' ?>"><?= ($sum_bal>=0?'+':'-').number_format(abs($sum_bal),2) ?></td>
      </tr>
    </tfoot>
  </table>
  </div>
</div>
<?php endif; ?>

<?php endif; // end tabs ?>

<!-- ═══════════════════════════ MODALS ═══════════════════════════ -->

<!-- Add/Edit Month (UNCHANGED) -->
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

<!-- Add/Edit Entry (UNCHANGED) -->
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

<!-- Add/Edit Revenue Entry (UNCHANGED) -->
<div class="modal-overlay" id="modal-rev-entry">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="rev-modal-title">Add Revenue Payment</div>
      <button class="modal-close" onclick="closeModal('modal-rev-entry')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="save_rev_entry">
      <input type="hidden" name="month_id" id="rev-modal-mid" value="<?= $view_mid ?: $rev_month_id ?? 0 ?>">
      <input type="hidden" name="rev_entry_id" id="rev-eid" value="">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Project Name *</label>
            <input type="text" name="project_name" id="rev-proj" class="form-control" placeholder="e.g. ABC Company Website" required>
          </div>
          <div class="form-group">
            <label class="form-label">Client Name</label>
            <input type="text" name="client_name" id="rev-client" class="form-control" placeholder="e.g. Mr. Ravi Kumar">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Payment Type</label>
            <select name="payment_type" id="rev-type" class="form-control">
              <option value="advance">🟡 Advance (Initial Pay)</option>
              <option value="milestone">🔵 Milestone (Middle Pay)</option>
              <option value="final">🟢 Final Pay (Completion)</option>
              <option value="other">⚪ Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Currency</label>
            <select name="currency" id="rev-cur" class="form-control">
              <option>INR</option><option>USD</option><option>EUR</option><option>LKR</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Amount Received</label>
            <input type="number" name="amount" id="rev-amt" step="0.01" class="form-control" value="0" min="0">
          </div>
          <div class="form-group">
            <label class="form-label">Payment Date</label>
            <input type="date" name="payment_date" id="rev-date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" id="rev-notes" class="form-control" style="min-height:56px" placeholder="e.g. Received via UPI, Invoice #123"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-rev-entry')">Cancel</button>
        <button type="submit" class="btn btn-primary">💾 Save Payment</button>
      </div>
    </form>
  </div>
</div>

<!-- Add/Edit Subscription (UNCHANGED) -->
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
          <div class="form-group"><label class="form-label">Invoice Number</label><input type="text" name="invoice_number" id="sub-inv" class="form-control" placeholder="e.g. OHYLCYIV-0006"></div>
          <div class="form-group"><label class="form-label">Paid To *</label><input type="text" name="paid_to" id="sub-pt" class="form-control" required placeholder="e.g. Anthropic"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Date of Issue</label><input type="date" name="date_of_issue" id="sub-di" class="form-control"></div>
          <div class="form-group"><label class="form-label">Date of End</label><input type="date" name="date_of_end" id="sub-de" class="form-control"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Paid Amount</label><input type="number" name="paid_amount" id="sub-amt" step="0.01" class="form-control" value="0"></div>
          <div class="form-group"><label class="form-label">Currency</label><select name="currency" id="sub-cur" class="form-control"><option>USD</option><option>INR</option><option>EUR</option><option>LKR</option></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Payment Method</label><input type="text" name="payment_method" id="sub-pm" class="form-control" placeholder="e.g. Visa •••• 4584"></div>
          <div class="form-group"><label class="form-label">Status</label><select name="status" id="sub-st" class="form-control"><option value="active">Active</option><option value="expired">Expired</option><option value="cancelled">Cancelled</option></select></div>
        </div>
        <div class="form-group"><label class="form-label">Paid By</label><select name="paid_by" id="sub-pb" class="form-control"><option value="">— Select —</option><?php foreach ($all_users as $u): ?><option value="<?= $u['id'] ?>"><?= h($u['name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label">Notes</label><textarea name="notes" id="sub-notes" class="form-control" style="min-height:60px"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-sub')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Subscription</button>
      </div>
    </form>
  </div>
</div>

<!-- Add/Edit Software Purchase (UNCHANGED) -->
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
          <div class="form-group"><label class="form-label">Invoice Number</label><input type="text" name="invoice_number" id="sw-inv" class="form-control" placeholder="e.g. 04752-21822961"></div>
          <div class="form-group"><label class="form-label">Paid To *</label><input type="text" name="paid_to" id="sw-pt" class="form-control" required placeholder="e.g. Canva"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Date Purchase</label><input type="date" name="date_purchase" id="sw-dp" class="form-control"></div>
          <div class="form-group"><label class="form-label">Date Expire</label><input type="date" name="date_expire" id="sw-de" class="form-control"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Usage Limit</label><input type="text" name="usage_limit" id="sw-ul" class="form-control" placeholder="e.g. 1 Day, 1 Week, Lifetime" list="ul-list"><datalist id="ul-list"><option>1 Day</option><option>1 Week</option><option>1 Month</option><option>1 Year</option><option>Lifetime</option></datalist></div>
          <div class="form-group"><label class="form-label">Currency</label><select name="currency" id="sw-cur" class="form-control"><option>INR</option><option>USD</option><option>EUR</option><option>LKR</option></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Paid Amount</label><input type="number" name="paid_amount" id="sw-amt" step="0.01" class="form-control" value="0"></div>
          <div class="form-group"><label class="form-label">Payment Method</label><input type="text" name="payment_method" id="sw-pm" class="form-control" placeholder="e.g. UPI QRCode"></div>
        </div>
        <div class="form-group"><label class="form-label">Paid By</label><select name="paid_by" id="sw-pb" class="form-control"><option value="">— Select —</option><?php foreach ($all_users as $u): ?><option value="<?= $u['id'] ?>"><?= h($u['name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label">Notes</label><input type="text" name="notes" id="sw-notes" class="form-control" placeholder="e.g. Social presence edits"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-sw')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Purchase</button>
      </div>
    </form>
  </div>
</div>

<script>
// ── QUICK INLINE SAVE (UNCHANGED) ──────────────────────────────────────────
const ownCache={}, officeCache={};
document.querySelectorAll('.exp-input[data-field="own"]').forEach(inp=>{ownCache[inp.dataset.eid]=parseFloat(inp.value)||0});
document.querySelectorAll('.exp-input[data-field="office"]').forEach(inp=>{officeCache[inp.dataset.eid]=parseFloat(inp.value)||0});
function quickSave(eid,own,office){
  if(own!==null) ownCache[eid]=parseFloat(own)||0;
  if(office!==null) officeCache[eid]=parseFloat(office)||0;
  fetch('expenses.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`action=quick_save&entry_id=${eid}&own_spend=${ownCache[eid]||0}&office_spend=${officeCache[eid]||0}`
  }).then(r=>r.json()).then(d=>{if(d.ok)toast('Saved','success')}).catch(()=>{});
}

// ── EDIT MONTH MODAL (UNCHANGED) ───────────────────────────────────────────
function editMonth(m){
  document.getElementById('month-modal-title').textContent='Edit Month';
  document.getElementById('mm-id').value=m.id;
  document.getElementById('mm-my').value=m.month_year;
  document.getElementById('mm-label').value=m.month_label;
  document.getElementById('mm-rev').value=m.revenue;
  document.getElementById('mm-notes').value=m.notes||'';
  openModal('modal-month');
}

// ── EDIT ENTRY MODAL (UNCHANGED) ───────────────────────────────────────────
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

// ── EDIT SUBSCRIPTION (UNCHANGED) ─────────────────────────────────────────
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

// ── EDIT SOFTWARE (UNCHANGED) ──────────────────────────────────────────────
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

// ── REVENUE MODAL (UNCHANGED) ──────────────────────────────────────────────
function openRevModal(){
  document.getElementById('rev-modal-title').textContent='Add Revenue Payment';
  document.getElementById('rev-eid').value='';
  if(document.getElementById('rev-modal-mid') && !document.getElementById('rev-modal-mid').value*1) document.getElementById('rev-modal-mid').value=<?= $rev_month_id ?? $view_mid ?? 0 ?>;
  document.getElementById('rev-proj').value='';
  document.getElementById('rev-client').value='';
  document.getElementById('rev-type').value='advance';
  document.getElementById('rev-cur').value='INR';
  document.getElementById('rev-amt').value='0';
  document.getElementById('rev-date').value=new Date().toISOString().slice(0,10);
  document.getElementById('rev-notes').value='';
  openModal('modal-rev-entry');
}
function openEditRevModal(r){
  document.getElementById('rev-modal-title').textContent='Edit Revenue Payment';
  document.getElementById('rev-eid').value=r.id;
  document.getElementById('rev-proj').value=r.project_name||'';
  document.getElementById('rev-client').value=r.client_name||'';
  document.getElementById('rev-type').value=r.payment_type||'advance';
  document.getElementById('rev-cur').value=r.currency||'INR';
  document.getElementById('rev-amt').value=r.amount||0;
  document.getElementById('rev-date').value=r.payment_date||'';
  document.getElementById('rev-notes').value=r.notes||'';
  openModal('modal-rev-entry');
}

// ── MONTHLY SUB-TAB SWITCH (UNCHANGED) ────────────────────────────────────
function switchMonthTab(tab){
  var tabs=['expenses','revenue'];
  tabs.forEach(function(t){
    var el=document.getElementById('mtab-'+t);
    var btn=document.querySelector('.rev-subtab[onclick*="\''+t+'\'"]');
    if(el) el.style.display=t===tab?'block':'none';
    if(btn) btn.classList.toggle('active',t===tab);
  });
}

// ── AUTO-FILL MONTH LABEL (UNCHANGED) ─────────────────────────────────────
document.getElementById('mm-my')?.addEventListener('change',function(){
  const d=new Date(this.value+'-01');
  const lbl=d.toLocaleString('en',{month:'long',year:'numeric'});
  document.getElementById('mm-label').value=lbl;
});
</script>

<?php renderLayoutEnd(); ?>