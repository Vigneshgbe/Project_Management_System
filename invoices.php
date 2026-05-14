<?php
/**
 * invoices.php — Padak CRM
 *
 * FIXES & UPGRADES (2025):
 *  - Removed duplicate "Prepared by" signature block (only Authorised Signatory + Stamp shown)
 *  - Fixed bind_param type-string bug: recur_interval was bound as 'i' (int) instead of 's' (string)
 *  - Fixed file_exists() using relative paths — now uses __DIR__ prefix consistently
 *  - Fixed SQL injection risk in search/status filter — now uses prepared statements
 *  - Fixed print CSS: sidebar/topbar/buttons reliably hidden via .no-print class
 *  - Fixed inv-meta-grid overflow on narrow screens (now auto-fill responsive)
 *  - Fixed: handleImageUpload returns absolute path internally, stores relative for DB/HTML
 *  - Fixed: $sig_path / $stamp_path resolved with __DIR__ for file_exists checks
 *  - REDESIGN: World-class professional invoice layout for multinational clients
 *  - Premium navy + slate + gold accent color system
 *  - Authoritative typography with DM Sans throughout for clarity
 *  - Full-bleed header band, structured grid, commanding signature section
 *  - All existing functionality, routing and design theme preserved intact
 *  - FIX (2026): Logo shown in original colors — removed brightness/invert filter
 *  - FIX (2026): Logo + company name rendered inline (side-by-side)
 *  - FIX (2026): All header text opacity raised for clear readability
 *  - FIX (2026): Invoice number, amounts, all data fields use DM Sans (clean, legible)
 *  - FIX (2026): Label micro-text opacity/size improved throughout
 */
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

// ── Schema bootstrap ─────────────────────────────────────────────────────────
@$db->query("CREATE TABLE IF NOT EXISTS invoice_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(200) DEFAULT NULL, company_tagline VARCHAR(200) DEFAULT NULL,
    company_address TEXT DEFAULT NULL,      company_phone VARCHAR(80) DEFAULT NULL,
    company_email VARCHAR(200) DEFAULT NULL, company_reg_no VARCHAR(100) DEFAULT NULL,
    company_vat VARCHAR(100) DEFAULT NULL,  company_logo VARCHAR(500) DEFAULT NULL,
    bank_name VARCHAR(200) DEFAULT NULL,    bank_account VARCHAR(100) DEFAULT NULL,
    bank_branch VARCHAR(200) DEFAULT NULL,  bank_swift VARCHAR(50) DEFAULT NULL,
    signature_image VARCHAR(500) DEFAULT NULL, stamp_image VARCHAR(500) DEFAULT NULL,
    invoice_footer TEXT DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
@$db->query("INSERT IGNORE INTO invoice_settings (id) VALUES (1)");
@$db->query("ALTER TABLE invoices ADD COLUMN IF NOT EXISTS signature_image VARCHAR(500) DEFAULT NULL AFTER terms");
@$db->query("ALTER TABLE invoices ADD COLUMN IF NOT EXISTS stamp_image VARCHAR(500) DEFAULT NULL AFTER signature_image");
@$db->query("CREATE TABLE IF NOT EXISTS invoice_counter (
    year INT NOT NULL PRIMARY KEY,
    seq INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if (!isManager()) { flash('Access restricted.','error'); header('Location: dashboard.php'); exit; }

// ── Helpers ──────────────────────────────────────────────────────────────────
function invSym(string $c): string {
    return match($c) { 'USD'=>'$','EUR'=>'€','GBP'=>'£','INR'=>'₹', default=>'Rs. ' };
}

function nextInvoiceNo(mysqli $db): string {
    $year = (int)date('Y');
    $db->query("INSERT INTO invoice_counter (year,seq) VALUES ($year,1)
                ON DUPLICATE KEY UPDATE seq=seq+1");
    $row = $db->query("SELECT seq FROM invoice_counter WHERE year=$year")->fetch_assoc();
    return 'INV-'.$year.'-'.str_pad((string)$row['seq'], 4, '0', STR_PAD_LEFT);
}

function recalcInvoice(mysqli $db, int $inv_id): void {
    $stmt = $db->prepare("SELECT COALESCE(SUM(amount),0) AS s FROM invoice_items WHERE invoice_id=?");
    $stmt->bind_param("i", $inv_id);
    $stmt->execute();
    $sub = (float)$stmt->get_result()->fetch_assoc()['s'];

    $stmt2 = $db->prepare("SELECT tax_rate, discount FROM invoices WHERE id=?");
    $stmt2->bind_param("i", $inv_id);
    $stmt2->execute();
    $inv = $stmt2->get_result()->fetch_assoc();
    $tax   = round($sub * ((float)$inv['tax_rate'] / 100), 2);
    $total = round($sub + $tax - (float)$inv['discount'], 2);

    $stmt3 = $db->prepare("UPDATE invoices SET subtotal=?, tax_amount=?, total=? WHERE id=?");
    $stmt3->bind_param("dddi", $sub, $tax, $total, $inv_id);
    $stmt3->execute();
}

function invColor(string $s): string {
    return match($s) {
        'paid'      => '#059669',
        'partial'   => '#d97706',
        'overdue'   => '#dc2626',
        'sent'      => '#4f46e5',
        'viewed'    => '#7c3aed',
        'draft'     => '#64748b',
        'cancelled' => '#475569',
        default     => '#64748b',
    };
}

function handleImageUpload(string $field, string $prefix): ?string {
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== 0) return null;
    $f   = $_FILES[$field];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) return null;
    if ($f['size'] > 2 * 1024 * 1024) return null;
    $dir = __DIR__ . '/uploads/invoice/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $fname = $prefix . '_' . uniqid() . '.' . $ext;
    if (move_uploaded_file($f['tmp_name'], $dir . $fname)) {
        return 'uploads/invoice/' . $fname;
    }
    return null;
}

function absPath(?string $rel): string {
    return $rel ? __DIR__ . '/' . ltrim($rel, '/') : '';
}

// ── POST HANDLERS ─────────────────────────────────────────────────────────────
ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_invoice_settings' && isAdmin()) {
        $text_fields = [
            'company_name','company_tagline','company_address','company_phone',
            'company_email','company_reg_no','company_vat',
            'bank_name','bank_account','bank_branch','bank_swift','invoice_footer',
        ];
        $sets  = [];
        $vals  = [];
        $types = '';
        foreach ($text_fields as $f) {
            $sets[]  = "$f=?";
            $vals[]  = trim($_POST[$f] ?? '');
            $types  .= 's';
        }
        $logo_path = handleImageUpload('company_logo', 'logo');
        if ($logo_path) { $sets[] = "company_logo=?";      $vals[] = $logo_path;  $types .= 's'; }
        $sig_path   = handleImageUpload('signature_image', 'sig');
        if ($sig_path)  { $sets[] = "signature_image=?";   $vals[] = $sig_path;   $types .= 's'; }
        $stamp_path = handleImageUpload('stamp_image', 'stamp');
        if ($stamp_path){ $sets[] = "stamp_image=?";       $vals[] = $stamp_path; $types .= 's'; }

        $vals[] = 1; $types .= 'i';
        $stmt = $db->prepare("UPDATE invoice_settings SET " . implode(',', $sets) . " WHERE id=?");
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();
        flash('Invoice settings saved.', 'success');
        ob_end_clean(); header('Location: invoices.php?tab=settings'); exit;
    }

    if (in_array($action, ['create_invoice','update_invoice'], true)) {
        if (!isManager()) { flash('Access denied.','error'); ob_end_clean(); header('Location: invoices.php'); exit; }

        $inv_id     = (int)($_POST['inv_id'] ?? 0);
        $title      = trim($_POST['title'] ?? '');
        $contact    = (int)($_POST['contact_id'] ?? 0) ?: null;
        $project    = (int)($_POST['project_id'] ?? 0) ?: null;
        $status     = $_POST['status']   ?? 'draft';
        $currency   = $_POST['currency'] ?? 'LKR';
        $issue      = $_POST['issue_date'] ?: date('Y-m-d');
        $due        = $_POST['due_date']   ?: null;
        $tax_rate   = (float)($_POST['tax_rate']  ?? 0);
        $discount   = (float)($_POST['discount']  ?? 0);
        $notes      = trim($_POST['notes']  ?? '');
        $terms      = trim($_POST['terms']  ?? '');
        $recur      = isset($_POST['is_recurring']) ? 1 : 0;
        $recur_int  = $recur ? (trim($_POST['recur_interval'] ?? '') ?: null) : null;
        $recur_next = $recur && !empty($_POST['recur_next']) ? $_POST['recur_next'] : null;

        if (!$title) { flash('Title required.','error'); ob_end_clean(); header('Location: invoices.php'); exit; }

        $sig_path   = handleImageUpload('signature_image', 'sig');
        $stamp_path = handleImageUpload('stamp_image', 'stamp');

        if ($action === 'create_invoice') {
            $inv_no = nextInvoiceNo($db);
            $stmt = $db->prepare(
                "INSERT INTO invoices
                    (invoice_no,title,contact_id,project_id,status,currency,
                     issue_date,due_date,tax_rate,discount,notes,terms,
                     is_recurring,recur_interval,recur_next,created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            );
            $stmt->bind_param(
                "ssiissssddssissi",
                $inv_no,$title,$contact,$project,$status,$currency,
                $issue,$due,$tax_rate,$discount,$notes,$terms,
                $recur,$recur_int,$recur_next,$uid
            );
            $stmt->execute();
            $inv_id = (int)$db->insert_id;

            if ($sig_path) {
                $s = $db->prepare("UPDATE invoices SET signature_image=? WHERE id=?");
                $s->bind_param("si",$sig_path,$inv_id); $s->execute();
            }
            if ($stamp_path) {
                $s = $db->prepare("UPDATE invoices SET stamp_image=? WHERE id=?");
                $s->bind_param("si",$stamp_path,$inv_id); $s->execute();
            }
            logActivity('created invoice', $title, $inv_id);

        } else {
            $stmt = $db->prepare(
                "UPDATE invoices
                 SET title=?,contact_id=?,project_id=?,status=?,currency=?,
                     issue_date=?,due_date=?,tax_rate=?,discount=?,notes=?,terms=?,
                     is_recurring=?,recur_interval=?,recur_next=?
                 WHERE id=?"
            );
            $stmt->bind_param(
                "siissssddssissi",
                $title,$contact,$project,$status,$currency,
                $issue,$due,$tax_rate,$discount,$notes,$terms,
                $recur,$recur_int,$recur_next,$inv_id
            );
            $stmt->execute();

            if ($sig_path) {
                $s = $db->prepare("UPDATE invoices SET signature_image=? WHERE id=?");
                $s->bind_param("si",$sig_path,$inv_id); $s->execute();
            }
            if ($stamp_path) {
                $s = $db->prepare("UPDATE invoices SET stamp_image=? WHERE id=?");
                $s->bind_param("si",$stamp_path,$inv_id); $s->execute();
            }
            logActivity('updated invoice', $title, $inv_id);
        }

        $del = $db->prepare("DELETE FROM invoice_items WHERE invoice_id=?");
        $del->bind_param("i",$inv_id); $del->execute();

        $si     = $db->prepare(
            "INSERT INTO invoice_items (invoice_id,description,quantity,unit_price,amount,sort_order)
             VALUES (?,?,?,?,?,?)"
        );
        $descs  = $_POST['item_desc']  ?? [];
        $qtys   = $_POST['item_qty']   ?? [];
        $prices = $_POST['item_price'] ?? [];
        foreach ($descs as $i => $desc) {
            $desc = trim($desc); if ($desc === '') continue;
            $qty  = max(0.01, (float)($qtys[$i]   ?? 1));
            $price= max(0,    (float)($prices[$i]  ?? 0));
            $amt  = round($qty * $price, 2);
            $si->bind_param("isdddi", $inv_id, $desc, $qty, $price, $amt, $i);
            $si->execute();
        }
        recalcInvoice($db, $inv_id);
        flash('Invoice saved.','success');
        ob_end_clean(); header('Location: invoices.php?view='.$inv_id); exit;
    }

    if ($action === 'delete_invoice' && isManager()) {
        $id  = (int)$_POST['inv_id'];
        $row = $db->query("SELECT title FROM invoices WHERE id=$id")->fetch_assoc();
        $db->query("DELETE FROM invoice_items    WHERE invoice_id=$id");
        $db->query("DELETE FROM invoice_payments WHERE invoice_id=$id");
        $db->query("DELETE FROM invoices         WHERE id=$id");
        logActivity('deleted invoice', $row['title'] ?? '', $id);
        flash('Invoice deleted.','success');
        ob_end_clean(); header('Location: invoices.php'); exit;
    }

    if ($action === 'mark_sent' && isManager()) {
        $id = (int)$_POST['inv_id'];
        $db->query("UPDATE invoices SET status='sent',sent_at=NOW() WHERE id=$id AND status='draft'");
        require_once 'includes/mailer.php';
        $inv_row = $db->query(
            "SELECT i.*,c.name AS cn,c.email AS ce
             FROM invoices i LEFT JOIN contacts c ON c.id=i.contact_id
             WHERE i.id=$id"
        )->fetch_assoc();
        if ($inv_row && $inv_row['ce']) {
            $tmpl = $db->query("SELECT body_html FROM email_templates WHERE name='Invoice Sent' LIMIT 1")->fetch_assoc();
            $html = $tmpl
                ? renderEmailTemplate($tmpl['body_html'], [
                    'name'       => $inv_row['cn'],
                    'invoice_no' => $inv_row['invoice_no'],
                    'amount'     => invSym($inv_row['currency']).number_format($inv_row['total'],2),
                    'due_date'   => $inv_row['due_date'] ? date('M j, Y',strtotime($inv_row['due_date'])) : 'On receipt',
                    'link'       => (defined('BASE_URL') ? BASE_URL : '').'/client_portal.php?page=invoices',
                  ])
                : '<p>Invoice '.$inv_row['invoice_no'].'</p>';
            $smtp = $db->query("SELECT from_email,from_name FROM email_settings WHERE is_default=1 LIMIT 1")->fetch_assoc();
            sendAndLog([
                'to'          => [$inv_row['cn'].' <'.$inv_row['ce'].'>'],
                'subject'     => 'Invoice '.$inv_row['invoice_no'].' — '.invSym($inv_row['currency']).number_format($inv_row['total'],2),
                'html'        => $html,
                'text'        => strip_tags($html),
                'from_email'  => $smtp['from_email'] ?? 'noreply@thepadak.com',
                'from_name'   => $smtp['from_name']  ?? 'Padak',
                'invoice_id'  => $id,
                'contact_id'  => $inv_row['contact_id'],
                'sent_by'     => $uid,
            ], $db);
        }
        flash('Invoice marked as sent.','success');
        ob_end_clean(); header('Location: invoices.php?view='.$id); exit;
    }

    if ($action === 'record_payment' && isManager()) {
        $inv_id = (int)$_POST['inv_id'];
        $amount = (float)$_POST['amount'];
        $method = $_POST['method']   ?? 'bank_transfer';
        $ref    = trim($_POST['reference'] ?? '');
        $date   = $_POST['paid_at']  ?: date('Y-m-d');
        $notes  = trim($_POST['pay_notes'] ?? '');

        $stmt = $db->prepare(
            "INSERT INTO invoice_payments (invoice_id,amount,method,reference,paid_at,notes,recorded_by)
             VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->bind_param("idssssi", $inv_id, $amount, $method, $ref, $date, $notes, $uid);
        $stmt->execute();

        $inv  = $db->query("SELECT total,amount_paid FROM invoices WHERE id=$inv_id")->fetch_assoc();
        $paid = round((float)$inv['amount_paid'] + $amount, 2);
        $nstat= $paid >= (float)$inv['total'] ? 'paid' : ($paid > 0 ? 'partial' : 'sent');
        $paid_col = $paid >= (float)$inv['total'] ? ",paid_at=NOW()" : "";
        $db->query("UPDATE invoices SET amount_paid=$paid,status='$nstat'$paid_col WHERE id=$inv_id");
        logActivity('recorded payment','invoice',$inv_id,"Amount: $amount");
        flash('Payment recorded.','success');
        ob_end_clean(); header('Location: invoices.php?view='.$inv_id); exit;
    }
}
ob_end_clean();

// ── LOAD DATA ─────────────────────────────────────────────────────────────────
$tab     = $_GET['tab'] ?? 'list';
$view_id = (int)($_GET['view'] ?? 0);
$edit_id = (int)($_GET['edit'] ?? 0);

$allowed_statuses = ['draft','sent','viewed','partial','paid','overdue','cancelled'];
$status_f = in_array($_GET['status'] ?? '', $allowed_statuses, true) ? $_GET['status'] : '';
$search   = trim($_GET['q'] ?? '');

$inv_settings = $db->query("SELECT * FROM invoice_settings WHERE id=1")->fetch_assoc() ?? [];
$contacts     = $db->query("SELECT id,name,company,email,address,phone FROM contacts WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$projects     = $db->query("SELECT id,title FROM projects WHERE status NOT IN('cancelled') ORDER BY title")->fetch_all(MYSQLI_ASSOC);

$where_parts = ["1=1"];
$bind_types  = "";
$bind_vals   = [];
if ($status_f) {
    $where_parts[] = "i.status=?";
    $bind_types   .= "s";
    $bind_vals[]   = $status_f;
}
if ($search) {
    $like = "%$search%";
    $where_parts[] = "(i.invoice_no LIKE ? OR i.title LIKE ? OR c.name LIKE ?)";
    $bind_types   .= "sss";
    $bind_vals[]   = $like;
    $bind_vals[]   = $like;
    $bind_vals[]   = $like;
}
$where_sql = implode(" AND ", $where_parts);

$list_sql = "
    SELECT i.*, c.name AS client_name, c.company, c.email AS client_email,
           c.address AS client_address, c.phone AS client_phone,
           p.title AS project_title,
           COALESCE(i.total - i.amount_paid, 0) AS balance_due
    FROM invoices i
    LEFT JOIN contacts c ON c.id = i.contact_id
    LEFT JOIN projects p ON p.id = i.project_id
    WHERE $where_sql
    ORDER BY i.created_at DESC
";
$list_stmt = $db->prepare($list_sql);
if ($bind_types) $list_stmt->bind_param($bind_types, ...$bind_vals);
$list_stmt->execute();
$invoices = $list_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stats = $db->query("
    SELECT
        SUM(CASE WHEN status NOT IN('cancelled','draft') THEN total ELSE 0 END) AS total_invoiced,
        SUM(amount_paid) AS total_collected,
        SUM(CASE WHEN status='overdue' THEN total-amount_paid ELSE 0 END) AS total_overdue,
        COUNT(CASE WHEN status='draft'   THEN 1 END) AS draft_count,
        COUNT(CASE WHEN status='overdue' THEN 1 END) AS overdue_count
    FROM invoices
")->fetch_assoc();

$inv = null; $items = []; $payments = [];
if ($view_id) {
    $s = $db->prepare(
        "SELECT i.*,
                c.name AS client_name, c.company, c.email AS client_email,
                c.address AS client_address, c.phone AS client_phone,
                p.title AS project_title
         FROM invoices i
         LEFT JOIN contacts c ON c.id=i.contact_id
         LEFT JOIN projects p ON p.id=i.project_id
         WHERE i.id=?"
    );
    $s->bind_param("i",$view_id); $s->execute();
    $inv = $s->get_result()->fetch_assoc();
    if ($inv) {
        $si = $db->prepare("SELECT * FROM invoice_items WHERE invoice_id=? ORDER BY sort_order,id");
        $si->bind_param("i",$view_id); $si->execute();
        $items = $si->get_result()->fetch_all(MYSQLI_ASSOC);

        $sp = $db->prepare(
            "SELECT ip.*, u.name AS recorded_by_name
             FROM invoice_payments ip
             LEFT JOIN users u ON u.id=ip.recorded_by
             WHERE ip.invoice_id=?
             ORDER BY ip.paid_at DESC"
        );
        $sp->bind_param("i",$view_id); $sp->execute();
        $payments = $sp->get_result()->fetch_all(MYSQLI_ASSOC);

        if ($inv['status'] === 'sent') {
            $db->query("UPDATE invoices SET status='viewed',viewed_at=NOW() WHERE id=$view_id AND status='sent'");
            $inv['status'] = 'viewed';
        }
    }
}

$edit_inv = null; $edit_items = [];
if ($edit_id) {
    $es = $db->prepare("SELECT * FROM invoices WHERE id=?");
    $es->bind_param("i",$edit_id); $es->execute();
    $edit_inv = $es->get_result()->fetch_assoc();
    $ei = $db->prepare("SELECT * FROM invoice_items WHERE invoice_id=? ORDER BY sort_order,id");
    $ei->bind_param("i",$edit_id); $ei->execute();
    $edit_items = $ei->get_result()->fetch_all(MYSQLI_ASSOC);
}

$db->query("UPDATE invoices SET status='overdue' WHERE status='sent' AND due_date < CURDATE() AND due_date IS NOT NULL");

renderLayout('Invoices','invoices');
?>

<style>
/* ═══════════════════════════════════════════════════════════════════════════
   INVOICES PAGE — Padak CRM  |  Professional Redesign (Fixed)
   Font stack: Playfair Display (company display name only) + DM Sans (all data)
   ═══════════════════════════════════════════════════════════════════════════ */

@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&display=swap');

/* ── CSS design tokens ── */
:root {
    --inv-navy:       #0f1b2d;
    --inv-navy-mid:   #1a2e45;
    --inv-slate:      #2d4a6b;
    --inv-gold:       #c9a84c;
    --inv-gold-light: #f0d990;
    --inv-offwhite:   #f7f6f3;
    --inv-rule:       #e2e0db;
    --inv-text-dark:  #0f1b2d;
    --inv-text-mid:   #3d5170;
    --inv-text-soft:  #6b7f99;
    --inv-green:      #047857;
    --inv-red:        #b91c1c;
    --inv-amber:      #b45309;
    /* ── FIX: DM Sans is the primary font everywhere; Playfair only for company display name ── */
    --inv-font-d:     'Playfair Display', Georgia, serif;
    --inv-font-b:     'DM Sans', system-ui, -apple-system, sans-serif;
    --inv-radius:     3px;
    --inv-shadow:     0 4px 32px rgba(15,27,45,.10), 0 1px 4px rgba(15,27,45,.06);
}

/* ── Stats strip ── */
.inv-stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-bottom:20px}
.inv-stat{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px}
.inv-stat-val{font-size:20px;font-weight:800;color:var(--text);margin-bottom:2px;font-family:var(--inv-font-b)}
.inv-stat-lbl{font-size:11.5px;color:var(--text3);text-transform:uppercase;letter-spacing:.05em}

/* ── Invoice list row ── */
.inv-row{display:flex;align-items:center;gap:14px;padding:13px 16px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:7px;cursor:pointer;transition:border-color .15s,box-shadow .15s}
.inv-row:hover{border-color:var(--border2);box-shadow:0 2px 8px rgba(0,0,0,.15)}
.inv-no{font-size:12px;font-weight:700;color:var(--text3);min-width:100px;font-family:var(--inv-font-b)}
.inv-client{flex:1;min-width:0}
.inv-client-name{font-size:13.5px;font-weight:700;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-family:var(--inv-font-b)}
.inv-client-sub{font-size:11.5px;color:var(--text3)}
.inv-amount{text-align:right;min-width:100px;flex-shrink:0}
.inv-total-d{font-size:14px;font-weight:700;color:var(--text);font-family:var(--inv-font-b)}
.inv-balance{font-size:11.5px;font-weight:600}

/* ── Detail layout ── */
.inv-detail-grid{display:grid;grid-template-columns:1fr 300px;gap:18px;align-items:start}

/* ── Status badge ── */
.inv-status{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;font-family:var(--inv-font-b)}

/* ── Tabs ── */
.inv-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:20px}
.inv-tab{padding:10px 18px;font-size:13px;font-weight:600;color:var(--text3);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s;text-decoration:none;white-space:nowrap}
.inv-tab:hover,.inv-tab.active{color:var(--orange);border-bottom-color:var(--orange)}

/* ── Payment record row ── */
.pay-row{display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--bg3);border-radius:var(--radius-sm);margin-bottom:6px}

/* ── Line item editor ── */
.item-editor-row{display:grid;grid-template-columns:1fr 80px 110px 100px 36px;gap:6px;align-items:center;margin-bottom:6px}

/* ── Settings page ── */
.settings-section{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:22px;margin-bottom:16px}
.settings-section-title{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--text3);padding-bottom:12px;border-bottom:1px solid var(--border);margin-bottom:18px}
.img-preview{max-height:60px;max-width:180px;object-fit:contain;border-radius:4px;margin-top:8px;display:block;border:1px solid var(--border)}

/* ── Meta grid ── */
.inv-meta-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin-bottom:20px}

/* ════════════════════════════════════════════════════════════
   PROFESSIONAL INVOICE DOCUMENT
   ════════════════════════════════════════════════════════════ */

/* Outer document wrapper */
.inv-document {
    background: #fff;
    border: 1px solid #d8d5ce;
    border-radius: 2px;
    box-shadow: var(--inv-shadow);
    overflow: hidden;
    /* FIX: DM Sans throughout for consistent, clear readability */
    font-family: var(--inv-font-b);
    color: var(--inv-text-dark);
}

/* ══════════════════════════════════════════════════════════
   HEADER BAND  —  Fixed layout & readability
   ══════════════════════════════════════════════════════════ */
.inv-doc-header {
    background: var(--inv-navy);
    padding: 32px 44px 30px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 40px;
}

/* ── Left side: brand row (logo + name inline) then contact meta below ── */
.inv-doc-header-left {
    display: flex;
    flex-direction: column;
    gap: 16px;
    flex: 1;
    min-width: 0;
}

/*
 * FIX: Logo and company name are now in a single flex row side-by-side.
 * Logo shows in its ORIGINAL colors — no filter applied.
 */
.inv-brand-row {
    display: flex;
    align-items: center;
    gap: 16px;
}

/* FIX: Removed filter: brightness(0) invert(1) — logo now shows original colors */
.inv-company-logo {
    max-height: 52px;
    max-width: 52px;
    width: auto;
    object-fit: contain;
    display: block;
    flex-shrink: 0;
    /* White pill background so any-color logo reads clearly on dark header */
    background: rgba(255,255,255,0.10);
    border-radius: 8px;
    padding: 6px;
}

.inv-brand-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

/* FIX: Playfair Display kept only for company display name — this is decorative and large enough to be clear */
.inv-company-name-hdr {
    font-family: var(--inv-font-d);
    font-size: 22px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: -.3px;
    line-height: 1.1;
}

/* FIX: Tagline opacity raised from .55 → .80 */
.inv-company-tagline-hdr {
    font-family: var(--inv-font-b);
    font-size: 10.5px;
    font-weight: 500;
    color: rgba(255,255,255,.80);
    letter-spacing: .14em;
    text-transform: uppercase;
    margin-top: 3px;
}

/* FIX: Contact meta opacity raised from .60 → .88, font-size 11.5→13px, DM Sans */
.inv-company-meta-hdr {
    font-family: var(--inv-font-b);
    font-size: 13px;
    font-weight: 400;
    color: rgba(255,255,255,.88);
    line-height: 1.9;
}

/* ── Right side of header ── */
.inv-doc-header-right {
    text-align: right;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}

/* FIX: "TAX INVOICE" label — DM Sans, opacity raised from gold to full gold, size up */
.inv-doc-label {
    font-family: var(--inv-font-b);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .22em;
    text-transform: uppercase;
    color: var(--inv-gold);
    margin-bottom: 6px;
}

/* FIX: Invoice number — DM Sans 800, crisp on dark background */
.inv-doc-number {
    font-family: var(--inv-font-b);
    font-size: 28px;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: -.5px;
    line-height: 1;
    margin-bottom: 14px;
}

/* FIX: "TOTAL AMOUNT" label — raised from rgba .45 → .75 */
.inv-doc-amount-label {
    font-family: var(--inv-font-b);
    font-size: 10px;
    font-weight: 600;
    color: rgba(255,255,255,.75);
    letter-spacing: .15em;
    text-transform: uppercase;
    margin-bottom: 5px;
}

/* FIX: Amount — DM Sans 800, gold-light, clean and bold */
.inv-doc-amount {
    font-family: var(--inv-font-b);
    font-size: 34px;
    font-weight: 800;
    color: var(--inv-gold-light);
    letter-spacing: -.5px;
    line-height: 1;
    margin-bottom: 8px;
}

.inv-doc-balance {
    font-family: var(--inv-font-b);
    font-size: 13px;
    font-weight: 600;
    color: #fca5a5;
    margin-top: 4px;
}

.inv-doc-paid-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(5,150,105,.22);
    border: 1px solid rgba(5,150,105,.45);
    border-radius: 4px;
    color: #6ee7b7;
    font-family: var(--inv-font-b);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .07em;
    padding: 5px 14px;
    margin-top: 6px;
    text-transform: uppercase;
}

.inv-doc-overdue-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(220,38,38,.22);
    border: 1px solid rgba(220,38,38,.40);
    border-radius: 4px;
    color: #fca5a5;
    font-family: var(--inv-font-b);
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: .08em;
    padding: 5px 12px;
    margin-top: 6px;
    text-transform: uppercase;
}

/* ── GOLD RULE ── */
.inv-gold-rule {
    height: 3px;
    background: linear-gradient(90deg, var(--inv-gold) 0%, var(--inv-gold-light) 40%, transparent 100%);
}

/* ── BODY AREA ── */
.inv-doc-body {
    padding: 36px 44px;
}

/* ── BILL FROM / BILL TO ── */
.inv-parties {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    margin-bottom: 28px;
    border: 1px solid var(--inv-rule);
    border-radius: var(--inv-radius);
    overflow: hidden;
}

.inv-party {
    padding: 20px 24px;
    background: var(--inv-offwhite);
}

.inv-party + .inv-party {
    border-left: 1px solid var(--inv-rule);
    background: #fff;
}

.inv-party-label {
    font-family: var(--inv-font-b);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: .22em;
    text-transform: uppercase;
    color: var(--inv-gold);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.inv-party-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--inv-gold);
    opacity: .35;
}

/* FIX: Party names use DM Sans 700 — clean, legible */
.inv-party-name {
    font-family: var(--inv-font-b);
    font-size: 15px;
    font-weight: 700;
    color: var(--inv-text-dark);
    margin-bottom: 2px;
    line-height: 1.2;
}

.inv-party-company {
    font-family: var(--inv-font-b);
    font-size: 12.5px;
    font-weight: 600;
    color: var(--inv-slate);
    margin-bottom: 6px;
}

.inv-party-detail {
    font-family: var(--inv-font-b);
    font-size: 12.5px;
    font-weight: 400;
    color: var(--inv-text-soft);
    line-height: 1.85;
}

/* ── META STRIP ── */
.inv-meta-strip {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 0;
    border: 1px solid var(--inv-rule);
    border-radius: var(--inv-radius);
    overflow: hidden;
    margin-bottom: 28px;
}

.inv-meta-cell {
    padding: 14px 18px;
    border-right: 1px solid var(--inv-rule);
    background: #fff;
}

.inv-meta-cell:last-child { border-right: none; }

/* FIX: Meta labels — DM Sans, clear */
.inv-meta-cell-lbl {
    font-family: var(--inv-font-b);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: .18em;
    text-transform: uppercase;
    color: var(--inv-text-soft);
    margin-bottom: 6px;
}

/* FIX: Meta values — DM Sans 700, clear legible size */
.inv-meta-cell-val {
    font-family: var(--inv-font-b);
    font-size: 13.5px;
    font-weight: 700;
    color: var(--inv-text-dark);
}

.inv-meta-cell-val.overdue-date { color: var(--inv-red); }

/* ── LINE ITEMS TABLE ── */
.inv-items-wrap {
    border: 1px solid var(--inv-rule);
    border-radius: var(--inv-radius);
    overflow: hidden;
    margin-bottom: 24px;
}

.inv-items-table {
    width: 100%;
    border-collapse: collapse;
    font-family: var(--inv-font-b);
}

.inv-items-table thead tr {
    background: var(--inv-navy);
}

/* FIX: Table headers — DM Sans, opacity raised from .65 → .90 */
.inv-items-table thead th {
    font-family: var(--inv-font-b);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: .18em;
    text-transform: uppercase;
    color: rgba(255,255,255,.90);
    padding: 13px 16px;
    text-align: left;
    border: none;
}

.inv-items-table thead th.r { text-align: right; }

.inv-items-table tbody tr {
    border-bottom: 1px solid var(--inv-rule);
    transition: background .1s;
}

.inv-items-table tbody tr:last-child { border-bottom: none; }
.inv-items-table tbody tr:nth-child(even) { background: var(--inv-offwhite); }

/* FIX: All table cells DM Sans, clear sizes */
.inv-items-table tbody td {
    font-family: var(--inv-font-b);
    padding: 13px 16px;
    font-size: 13.5px;
    color: var(--inv-text-dark);
    vertical-align: middle;
}

.inv-items-table tbody td.r { text-align: right; }

.inv-items-table tbody td.item-no {
    font-size: 12px;
    font-weight: 600;
    color: var(--inv-text-soft);
    width: 36px;
}

.inv-items-table tbody td.item-desc {
    font-weight: 500;
    color: var(--inv-text-dark);
    font-size: 13.5px;
}

.inv-items-table tbody td.item-qty,
.inv-items-table tbody td.item-price {
    color: var(--inv-text-mid);
    font-size: 13px;
    font-weight: 500;
}

/* FIX: Amount column — DM Sans 700, clear weight */
.inv-items-table tbody td.item-amount {
    font-family: var(--inv-font-b);
    font-weight: 700;
    color: var(--inv-text-dark);
    font-size: 14px;
}

/* ── TOTALS BLOCK ── */
.inv-totals-wrap {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 28px;
}

.inv-totals-inner {
    min-width: 320px;
    border: 1px solid var(--inv-rule);
    border-radius: var(--inv-radius);
    overflow: hidden;
}

.inv-totals-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 11px 20px;
    border-bottom: 1px solid var(--inv-rule);
    color: var(--inv-text-mid);
}

.inv-totals-row:last-child { border-bottom: none; }
.inv-totals-row.tax-row,
.inv-totals-row.sub-row { background: #fff; }
.inv-totals-row.disc-row { background: #fff; color: var(--inv-green); }

.inv-totals-row.grand-row {
    background: var(--inv-navy);
    border-bottom: none;
    padding: 18px 20px;
}

/* FIX: Grand total label — DM Sans, opacity raised */
.inv-totals-row.grand-row .tot-label {
    font-family: var(--inv-font-b);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .16em;
    text-transform: uppercase;
    color: rgba(255,255,255,.80);
}

/* FIX: Grand total value — DM Sans 800, gold-light, large clear number */
.inv-totals-row.grand-row .tot-value {
    font-family: var(--inv-font-b);
    font-size: 24px;
    font-weight: 800;
    color: var(--inv-gold-light);
    letter-spacing: -.3px;
}

.inv-totals-row.paid-row {
    background: rgba(5,150,105,.06);
    color: var(--inv-green);
    font-weight: 600;
}

.inv-totals-row.balance-row {
    background: rgba(185,28,28,.05);
    color: var(--inv-red);
    font-weight: 700;
    font-size: 14px;
}

/* FIX: All totals use DM Sans */
.tot-label {
    font-family: var(--inv-font-b);
    font-size: 13px;
    font-weight: 500;
}

.tot-value {
    font-family: var(--inv-font-b);
    font-size: 14px;
    font-weight: 700;
}

/* ── BANK DETAILS ── */
.inv-bank-section {
    border: 1px solid var(--inv-rule);
    border-radius: var(--inv-radius);
    overflow: hidden;
    margin-bottom: 24px;
}

.inv-bank-header {
    background: var(--inv-offwhite);
    border-bottom: 1px solid var(--inv-rule);
    padding: 10px 20px;
    font-family: var(--inv-font-b);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: .2em;
    text-transform: uppercase;
    color: var(--inv-text-soft);
    display: flex;
    align-items: center;
    gap: 8px;
}

.inv-bank-header::before {
    content: '';
    display: inline-block;
    width: 12px;
    height: 2px;
    background: var(--inv-gold);
}

.inv-bank-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 0;
    padding: 0;
    background: #fff;
}

.inv-bank-item {
    padding: 14px 20px;
    border-right: 1px solid var(--inv-rule);
}

.inv-bank-item:last-child { border-right: none; }

.inv-bank-lbl {
    font-family: var(--inv-font-b);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: .16em;
    text-transform: uppercase;
    color: var(--inv-text-soft);
    margin-bottom: 5px;
}

/* FIX: Bank values DM Sans, larger and bolder */
.inv-bank-val {
    font-family: var(--inv-font-b);
    font-size: 13.5px;
    font-weight: 700;
    color: var(--inv-text-dark);
}

/* ── NOTES & TERMS ── */
.inv-notes-terms {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 32px;
}

.inv-note-card {
    border: 1px solid var(--inv-rule);
    border-radius: var(--inv-radius);
    overflow: hidden;
}

.inv-note-card-header {
    background: var(--inv-offwhite);
    border-bottom: 1px solid var(--inv-rule);
    padding: 9px 16px;
    font-family: var(--inv-font-b);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: .18em;
    text-transform: uppercase;
    color: var(--inv-text-soft);
}

.inv-note-card-body {
    padding: 14px 16px;
    font-family: var(--inv-font-b);
    font-size: 13px;
    color: var(--inv-text-mid);
    line-height: 1.8;
    background: #fff;
}

/* ══════════════════════════════════════════════════════
   SIGNATURE SECTION
   ══════════════════════════════════════════════════════ */
.inv-sign-section {
    border-top: 1px solid var(--inv-rule);
    padding-top: 32px;
    margin-top: 8px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
}

.inv-sign-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0 32px 0;
}

.inv-sign-col + .inv-sign-col {
    border-left: 1px solid var(--inv-rule);
}

.inv-sign-img-area {
    height: 80px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    margin-bottom: 12px;
    width: 100%;
}

.inv-sign-img {
    max-height: 72px;
    max-width: 200px;
    object-fit: contain;
    display: block;
}

.inv-stamp-img {
    max-height: 80px;
    max-width: 80px;
    object-fit: contain;
    display: block;
    opacity: .92;
}

.inv-sign-placeholder {
    width: 200px;
    height: 60px;
    border-bottom: 1.5px solid #c8c4bb;
}

.inv-stamp-placeholder {
    width: 72px;
    height: 72px;
    border: 2px dashed #c8c4bb;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--inv-font-b);
    font-size: 9px;
    letter-spacing: .12em;
    color: #aaa9a5;
    text-transform: uppercase;
}

.inv-sign-rule {
    width: 100%;
    max-width: 220px;
    border-top: 1.5px solid var(--inv-navy);
    padding-top: 9px;
    text-align: center;
}

.inv-sign-role {
    font-family: var(--inv-font-b);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: .2em;
    text-transform: uppercase;
    color: var(--inv-text-soft);
    margin-bottom: 4px;
}

.inv-sign-entity {
    font-family: var(--inv-font-b);
    font-size: 13.5px;
    font-weight: 700;
    color: var(--inv-text-dark);
}

.inv-sign-reg {
    font-family: var(--inv-font-b);
    font-size: 11px;
    color: var(--inv-text-soft);
    margin-top: 2px;
}

/* ── DOCUMENT FOOTER BAND ── */
.inv-doc-footer {
    background: var(--inv-navy);
    padding: 16px 44px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-top: 32px;
}

/* FIX: Footer text — DM Sans, opacity raised from .45 → .70 */
.inv-doc-footer-text {
    font-family: var(--inv-font-b);
    font-size: 12px;
    font-weight: 400;
    color: rgba(255,255,255,.70);
    line-height: 1.6;
}

/* FIX: Footer ref — DM Sans, opacity raised from .30 → .55 */
.inv-doc-footer-ref {
    font-family: var(--inv-font-b);
    font-size: 11px;
    font-weight: 600;
    color: rgba(255,255,255,.55);
    text-align: right;
    flex-shrink: 0;
    letter-spacing: .04em;
}

/* ══════════════════════════════════════════════════════════
   PRINT / PDF  —  Clean, professional
   ══════════════════════════════════════════════════════════ */
@media print {
    @page { size: A4; margin: 0; }

    .no-print,
    .nav-sidebar, .sidebar, .topbar, .page-header,
    .top-actions, .modal-overlay,
    .btn, button, a.btn,
    [class*="action-bar"],
    .inv-tabs,
    .inv-stats { display: none !important; }

    body { background: #fff !important; margin: 0 !important; padding: 0 !important; }
    .inv-detail-grid { display: block !important; }
    .inv-detail-grid > div:last-child { display: none !important; }
    .inv-document {
        box-shadow: none !important;
        border: none !important;
        border-radius: 0 !important;
    }
    .inv-doc-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .inv-doc-footer { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .inv-items-table thead tr { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .inv-totals-row.grand-row { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .inv-sign-section { page-break-inside: avoid; }
    .card { border: none !important; box-shadow: none !important; }
}

/* ── Responsive ── */
@media (max-width: 960px) {
    .inv-detail-grid { grid-template-columns: 1fr; }
}

@media (max-width: 700px) {
    .inv-doc-header { flex-direction: column; gap: 20px; padding: 24px; }
    .inv-doc-header-right { text-align: left; align-items: flex-start; }
    .inv-doc-body { padding: 24px; }
    .inv-parties { grid-template-columns: 1fr; }
    .inv-party + .inv-party { border-left: none; border-top: 1px solid var(--inv-rule); }
    .inv-notes-terms { grid-template-columns: 1fr; }
    .inv-sign-section { grid-template-columns: 1fr; max-width: 280px; margin: 0 auto; }
    .inv-sign-col + .inv-sign-col { border-left: none; border-top: 1px solid var(--inv-rule); padding-top: 24px; margin-top: 24px; }
    .inv-stats { grid-template-columns: 1fr 1fr; }
    .item-editor-row { grid-template-columns: 1fr 70px 90px 80px 30px; }
    .inv-meta-strip { grid-template-columns: 1fr 1fr; }
    .inv-doc-footer { flex-direction: column; text-align: center; padding: 16px 24px; }
    .inv-doc-footer-ref { text-align: center; }
    .inv-brand-row { gap: 12px; }
    .inv-company-logo { max-height: 40px; max-width: 40px; }
    .inv-company-name-hdr { font-size: 18px; }
}
</style>


<?php /* ═════════════════════════════════════════════════════════════════
       SINGLE INVOICE VIEW
       ═════════════════════════════════════════════════════════════════ */
if ($inv): ?>

<?php
$sym        = invSym($inv['currency']);
$sc         = invColor($inv['status']);
$co         = $inv_settings;
$is_overdue = $inv['due_date'] && $inv['due_date'] < date('Y-m-d') && $inv['status'] !== 'paid';

$sig_path   = !empty($inv['signature_image'])
                ? (file_exists(absPath($inv['signature_image']))   ? $inv['signature_image']   : ($co['signature_image'] ?? ''))
                : ($co['signature_image'] ?? '');
$stamp_path = !empty($inv['stamp_image'])
                ? (file_exists(absPath($inv['stamp_image']))       ? $inv['stamp_image']       : ($co['stamp_image'] ?? ''))
                : ($co['stamp_image'] ?? '');
$logo_path  = $co['company_logo'] ?? '';
$balance    = max(0, (float)$inv['total'] - (float)$inv['amount_paid']);
?>

<!-- Top action bar -->
<div class="no-print" style="margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
    <a href="invoices.php" style="color:var(--text3);font-size:13px;text-decoration:none">← All Invoices</a>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <?php if ($inv['status']==='draft' && isManager()): ?>
        <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="mark_sent">
            <input type="hidden" name="inv_id" value="<?= $view_id ?>">
            <button class="btn btn-ghost btn-sm">📤 Mark Sent</button>
        </form>
        <?php endif; ?>
        <?php if (in_array($inv['status'],['sent','viewed','partial','overdue'],true) && isManager()): ?>
        <button class="btn btn-primary btn-sm" onclick="openModal('modal-payment')">💳 Record Payment</button>
        <?php endif; ?>
        <?php if (isManager()): ?>
        <a href="invoices.php?edit=<?= $view_id ?>" class="btn btn-ghost btn-sm">✎ Edit</a>
        <button class="btn btn-ghost btn-sm" onclick="window.print()">🖨 Print / PDF</button>
        <form method="POST" onsubmit="return confirm('Delete this invoice? This cannot be undone.')" style="display:inline">
            <input type="hidden" name="action" value="delete_invoice">
            <input type="hidden" name="inv_id" value="<?= $view_id ?>">
            <button class="btn btn-danger btn-sm">🗑</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="inv-detail-grid">
  <!-- ─── LEFT: Invoice document ─── -->
  <div>
    <div class="inv-document">

      <!-- ══ HEADER BAND ══ -->
      <div class="inv-doc-header">

        <!-- FIX: Logo and company name now side-by-side in .inv-brand-row -->
        <div class="inv-doc-header-left">
          <div class="inv-brand-row">
            <?php if ($logo_path && file_exists(absPath($logo_path))): ?>
            <img src="<?= h($logo_path) ?>" class="inv-company-logo" alt="<?= h($co['company_name'] ?? 'Company') ?> Logo">
            <?php endif; ?>
            <div class="inv-brand-text">
              <div class="inv-company-name-hdr"><?= h($co['company_name'] ?? 'Padak') ?></div>
              <?php if (!empty($co['company_tagline'])): ?>
              <div class="inv-company-tagline-hdr"><?= h($co['company_tagline']) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="inv-company-meta-hdr">
            <?php if (!empty($co['company_address'])): ?><?= nl2br(h($co['company_address'])) ?><br><?php endif; ?>
            <?php if (!empty($co['company_phone'])): ?><?= h($co['company_phone']) ?><br><?php endif; ?>
            <?php if (!empty($co['company_email'])): ?><?= h($co['company_email']) ?><br><?php endif; ?>
            <?php if (!empty($co['company_reg_no'])): ?>Reg. <?= h($co['company_reg_no']) ?><?php endif; ?>
            <?php if (!empty($co['company_vat'])): ?> &nbsp;·&nbsp; VAT <?= h($co['company_vat']) ?><?php endif; ?>
          </div>
        </div>

        <div class="inv-doc-header-right">
          <div class="inv-doc-label">Tax Invoice</div>
          <div class="inv-doc-number"><?= h($inv['invoice_no']) ?></div>
          <div class="inv-doc-amount-label">Total Amount</div>
          <div class="inv-doc-amount"><?= $sym ?><?= number_format((float)$inv['total'],2) ?></div>
          <?php if ($inv['status'] === 'paid'): ?>
            <div><span class="inv-doc-paid-badge">✓ Paid in Full</span></div>
          <?php elseif ($is_overdue): ?>
            <div><span class="inv-doc-overdue-badge">⚠ Overdue</span></div>
            <?php if ($balance > 0): ?>
            <div class="inv-doc-balance">Balance Due: <?= $sym ?><?= number_format($balance,2) ?></div>
            <?php endif; ?>
          <?php elseif ($balance > 0): ?>
            <div class="inv-doc-balance">Balance Due: <?= $sym ?><?= number_format($balance,2) ?></div>
          <?php endif; ?>
        </div>

      </div><!-- /.inv-doc-header -->

      <!-- Gold rule -->
      <div class="inv-gold-rule"></div>

      <!-- ══ BODY ══ -->
      <div class="inv-doc-body">

        <!-- ── BILL FROM / BILL TO ── -->
        <div class="inv-parties">
          <div class="inv-party">
            <div class="inv-party-label">From</div>
            <div class="inv-party-name"><?= h($co['company_name'] ?? 'Padak') ?></div>
            <?php if (!empty($co['company_address'])): ?><div class="inv-party-detail"><?= nl2br(h($co['company_address'])) ?></div><?php endif; ?>
            <?php if (!empty($co['company_email'])): ?><div class="inv-party-detail"><?= h($co['company_email']) ?></div><?php endif; ?>
            <?php if (!empty($co['company_phone'])): ?><div class="inv-party-detail"><?= h($co['company_phone']) ?></div><?php endif; ?>
            <?php if (!empty($co['company_reg_no'])): ?><div class="inv-party-detail" style="margin-top:4px;font-weight:600;font-size:12px;color:var(--inv-text-mid)">Reg: <?= h($co['company_reg_no']) ?></div><?php endif; ?>
          </div>
          <div class="inv-party">
            <div class="inv-party-label">Bill To</div>
            <div class="inv-party-name"><?= h($inv['client_name'] ?? '—') ?></div>
            <?php if ($inv['company']): ?><div class="inv-party-company"><?= h($inv['company']) ?></div><?php endif; ?>
            <?php if ($inv['client_address']): ?><div class="inv-party-detail"><?= nl2br(h($inv['client_address'])) ?></div><?php endif; ?>
            <?php if ($inv['client_email']): ?><div class="inv-party-detail"><?= h($inv['client_email']) ?></div><?php endif; ?>
            <?php if ($inv['client_phone']): ?><div class="inv-party-detail"><?= h($inv['client_phone']) ?></div><?php endif; ?>
          </div>
        </div>

        <!-- ── META STRIP ── -->
        <div class="inv-meta-strip">
          <div class="inv-meta-cell">
            <div class="inv-meta-cell-lbl">Invoice No</div>
            <div class="inv-meta-cell-val"><?= h($inv['invoice_no']) ?></div>
          </div>
          <div class="inv-meta-cell">
            <div class="inv-meta-cell-lbl">Issue Date</div>
            <div class="inv-meta-cell-val"><?= fDate($inv['issue_date']) ?></div>
          </div>
          <div class="inv-meta-cell">
            <div class="inv-meta-cell-lbl">Due Date</div>
            <div class="inv-meta-cell-val<?= $is_overdue ? ' overdue-date' : '' ?>">
              <?= $inv['due_date'] ? fDate($inv['due_date']) : 'On Receipt' ?>
            </div>
          </div>
          <div class="inv-meta-cell">
            <div class="inv-meta-cell-lbl">Currency</div>
            <div class="inv-meta-cell-val"><?= h($inv['currency'] ?? 'LKR') ?></div>
          </div>
          <?php if ($inv['project_title']): ?>
          <div class="inv-meta-cell">
            <div class="inv-meta-cell-lbl">Project</div>
            <div class="inv-meta-cell-val" style="font-size:12px"><?= h($inv['project_title']) ?></div>
          </div>
          <?php endif; ?>
          <div class="inv-meta-cell">
            <div class="inv-meta-cell-lbl">Status</div>
            <div class="inv-meta-cell-val"><span class="inv-status" style="background:<?= $sc ?>18;color:<?= $sc ?>"><?= ucfirst($inv['status']) ?></span></div>
          </div>
        </div>

        <!-- ── LINE ITEMS TABLE ── -->
        <div class="inv-items-wrap">
          <table class="inv-items-table">
            <thead>
              <tr>
                <th style="width:36px">#</th>
                <th>Description</th>
                <th class="r">Qty</th>
                <th class="r">Unit Price</th>
                <th class="r">Amount</th>
              </tr>
            </thead>
            <tbody>
            <?php if ($items): foreach ($items as $idx => $it): ?>
            <tr>
              <td class="item-no"><?= $idx + 1 ?></td>
              <td class="item-desc"><?= h($it['description']) ?></td>
              <td class="r item-qty"><?= rtrim(rtrim(number_format((float)$it['quantity'],2,'.',','),'0'),'.') ?></td>
              <td class="r item-price"><?= $sym ?><?= number_format((float)$it['unit_price'],2) ?></td>
              <td class="r item-amount"><?= $sym ?><?= number_format((float)$it['amount'],2) ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--inv-text-soft);font-style:italic;font-family:var(--inv-font-b)">No line items</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- ── TOTALS ── -->
        <div class="inv-totals-wrap">
          <div class="inv-totals-inner">
            <div class="inv-totals-row sub-row">
              <span class="tot-label">Subtotal</span>
              <span class="tot-value"><?= $sym ?><?= number_format((float)$inv['subtotal'],2) ?></span>
            </div>
            <?php if ((float)$inv['tax_rate'] > 0): ?>
            <div class="inv-totals-row tax-row">
              <span class="tot-label">Tax (<?= h($inv['tax_rate']) ?>%)</span>
              <span class="tot-value"><?= $sym ?><?= number_format((float)$inv['tax_amount'],2) ?></span>
            </div>
            <?php endif; ?>
            <?php if ((float)$inv['discount'] > 0): ?>
            <div class="inv-totals-row disc-row">
              <span class="tot-label">Discount</span>
              <span class="tot-value">−<?= $sym ?><?= number_format((float)$inv['discount'],2) ?></span>
            </div>
            <?php endif; ?>
            <div class="inv-totals-row grand-row">
              <span class="tot-label">Total Due</span>
              <span class="tot-value"><?= $sym ?><?= number_format((float)$inv['total'],2) ?></span>
            </div>
            <?php if ((float)$inv['amount_paid'] > 0): ?>
            <div class="inv-totals-row paid-row">
              <span class="tot-label">Amount Paid</span>
              <span class="tot-value"><?= $sym ?><?= number_format((float)$inv['amount_paid'],2) ?></span>
            </div>
            <?php if ($inv['status'] !== 'paid'): ?>
            <div class="inv-totals-row balance-row">
              <span class="tot-label">Balance Due</span>
              <span class="tot-value"><?= $sym ?><?= number_format($balance,2) ?></span>
            </div>
            <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- ── BANK DETAILS ── -->
        <?php if (!empty($co['bank_name']) || !empty($co['bank_account'])): ?>
        <div class="inv-bank-section">
          <div class="inv-bank-header">Payment &amp; Bank Details</div>
          <div class="inv-bank-grid">
            <?php foreach ([
              ['Bank Name',   $co['bank_name']    ?? ''],
              ['Account No',  $co['bank_account'] ?? ''],
              ['Branch',      $co['bank_branch']  ?? ''],
              ['SWIFT / BIC', $co['bank_swift']   ?? ''],
            ] as [$lbl,$val]): if (!$val) continue; ?>
            <div class="inv-bank-item">
              <div class="inv-bank-lbl"><?= $lbl ?></div>
              <div class="inv-bank-val"><?= h($val) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- ── NOTES & TERMS ── -->
        <?php if ($inv['notes'] || $inv['terms']): ?>
        <div class="inv-notes-terms">
          <?php if ($inv['notes']): ?>
          <div class="inv-note-card">
            <div class="inv-note-card-header">Notes</div>
            <div class="inv-note-card-body"><?= nl2br(h($inv['notes'])) ?></div>
          </div>
          <?php endif; ?>
          <?php if ($inv['terms']): ?>
          <div class="inv-note-card">
            <div class="inv-note-card-header">Terms &amp; Conditions</div>
            <div class="inv-note-card-body" style="font-size:12.5px"><?= nl2br(h($inv['terms'])) ?></div>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ══ SIGNATURE & STAMP ══ -->
        <div class="inv-sign-section">
          <!-- Authorised Signatory -->
          <div class="inv-sign-col">
            <div class="inv-sign-img-area">
              <?php if ($sig_path && file_exists(absPath($sig_path))): ?>
              <img src="<?= h($sig_path) ?>" class="inv-sign-img" alt="Authorised Signature">
              <?php else: ?>
              <div class="inv-sign-placeholder"></div>
              <?php endif; ?>
            </div>
            <div class="inv-sign-rule">
              <div class="inv-sign-role">Authorised Signatory</div>
              <div class="inv-sign-entity"><?= h($co['company_name'] ?? 'Padak') ?></div>
              <?php if (!empty($co['company_reg_no'])): ?>
              <div class="inv-sign-reg">Reg: <?= h($co['company_reg_no']) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <!-- Company Stamp -->
          <div class="inv-sign-col">
            <div class="inv-sign-img-area">
              <?php if ($stamp_path && file_exists(absPath($stamp_path))): ?>
              <img src="<?= h($stamp_path) ?>" class="inv-stamp-img" alt="Company Stamp">
              <?php else: ?>
              <div class="inv-stamp-placeholder">Stamp</div>
              <?php endif; ?>
            </div>
            <div class="inv-sign-rule">
              <div class="inv-sign-role">Company Stamp</div>
              <div class="inv-sign-entity"><?= h($co['company_name'] ?? 'Padak') ?></div>
              <?php if (!empty($co['company_reg_no'])): ?>
              <div class="inv-sign-reg">Reg: <?= h($co['company_reg_no']) ?></div>
              <?php endif; ?>
            </div>
          </div>
        </div>

      </div><!-- /.inv-doc-body -->

      <!-- ══ DOCUMENT FOOTER BAND ══ -->
      <div class="inv-doc-footer">
        <div class="inv-doc-footer-text">
          <?php if (!empty($co['invoice_footer'])): ?>
          <?= h($co['invoice_footer']) ?>
          <?php else: ?>
          Thank you for your business. Please make payment by the due date.
          <?php endif; ?>
        </div>
        <div class="inv-doc-footer-ref">
          <?= h($inv['invoice_no']) ?> &nbsp;·&nbsp; <?= date('Y') ?><br>
          <span style="font-size:9px;opacity:.6">Computer generated document</span>
        </div>
      </div>

    </div><!-- /.inv-document -->

    <!-- Payment history (below the document) -->
    <?php if ($payments): ?>
    <div class="card no-print" style="margin-top:16px">
      <div class="card-header"><div class="card-title">💳 Payment History</div></div>
      <?php
      $pay_icons = ['bank_transfer'=>'🏦','cash'=>'💵','card'=>'💳','cheque'=>'📄','online'=>'🌐','other'=>'💰'];
      foreach ($payments as $py):
        $pico = $pay_icons[$py['method']] ?? '💰';
      ?>
      <div class="pay-row">
        <span style="font-size:20px"><?= $pico ?></span>
        <div style="flex:1">
          <div style="font-size:13px;font-weight:600;color:var(--green)"><?= $sym ?><?= number_format((float)$py['amount'],2) ?></div>
          <div style="font-size:11.5px;color:var(--text3)">
            <?= h(ucfirst(str_replace('_',' ',$py['method']))) ?>
            <?= $py['reference'] ? ' · Ref: '.h($py['reference']) : '' ?>
            · Recorded by <?= h($py['recorded_by_name'] ?? 'System') ?>
          </div>
        </div>
        <div style="font-size:12px;color:var(--text3)"><?= fDate($py['paid_at']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div><!-- /.left col -->

  <!-- ─── RIGHT: Sidebar info ─── -->
  <div class="no-print">
    <div class="card" style="margin-bottom:14px">
      <div class="card-title" style="margin-bottom:12px">Invoice Info</div>
      <?php foreach ([
        ['Status',    '<span class="inv-status" style="background:'.invColor($inv['status']).'18;color:'.invColor($inv['status']).'">'.ucfirst($inv['status']).'</span>'],
        ['Recurring', $inv['is_recurring'] ? '🔁 '.ucfirst($inv['recur_interval']??'').' · Next: '.fDate($inv['recur_next']) : 'No'],
        ['Sent',      $inv['sent_at']   ? fDate($inv['sent_at'],  'M j, Y g:ia') : '—'],
        ['Viewed',    $inv['viewed_at'] ? fDate($inv['viewed_at'],'M j, Y g:ia') : '—'],
        ['Paid',      $inv['paid_at']   ? fDate($inv['paid_at'],  'M j, Y')      : '—'],
      ] as [$l,$v]): ?>
      <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border);font-size:12.5px">
        <span style="color:var(--text3)"><?= $l ?></span>
        <span style="color:var(--text2);font-weight:600;text-align:right;max-width:160px"><?= $v ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($inv['client_email'] || $inv['client_name']): ?>
    <div class="card" style="margin-bottom:14px">
      <div class="card-title" style="margin-bottom:12px">Client Contact</div>
      <div style="font-size:13px;font-weight:600;color:var(--text)"><?= h($inv['client_name']) ?></div>
      <?php if ($inv['company']): ?><div style="font-size:12px;color:var(--text3)"><?= h($inv['company']) ?></div><?php endif; ?>
      <?php if ($inv['client_email']): ?><a href="mailto:<?= h($inv['client_email']) ?>" style="font-size:12px;color:var(--orange)"><?= h($inv['client_email']) ?></a><?php endif; ?>
      <?php if ($inv['client_phone']): ?><div style="font-size:11.5px;color:var(--text3);margin-top:4px"><?= h($inv['client_phone']) ?></div><?php endif; ?>
      <?php if ($inv['client_address']): ?><div style="font-size:11.5px;color:var(--text3);margin-top:4px"><?= nl2br(h($inv['client_address'])) ?></div><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (isAdmin()): ?>
    <div class="card" style="border-color:var(--orange);background:var(--orange-bg)">
      <div class="card-title" style="margin-bottom:10px;color:var(--orange)">⚙ Invoice Settings</div>
      <p style="font-size:12px;color:var(--text2);margin-bottom:10px">Configure logo, signature, stamp and bank details for all invoices.</p>
      <a href="invoices.php?tab=settings" class="btn btn-ghost btn-sm" style="text-decoration:none">Open Settings →</a>
    </div>
    <?php endif; ?>
  </div>
</div><!-- /.inv-detail-grid -->

<!-- PAYMENT MODAL -->
<div class="modal-overlay" id="modal-payment">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <div class="modal-title">💳 Record Payment</div>
      <button class="modal-close" onclick="closeModal('modal-payment')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action"  value="record_payment">
      <input type="hidden" name="inv_id"  value="<?= $view_id ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Amount *</label>
            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required
                   value="<?= number_format($balance,2,'.','') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Date</label>
            <input type="date" name="paid_at" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Method</label>
          <select name="method" class="form-control">
            <?php foreach (['bank_transfer'=>'Bank Transfer','cash'=>'Cash','card'=>'Card / Cheque','online'=>'Online Payment','other'=>'Other'] as $mv=>$ml): ?>
            <option value="<?= $mv ?>"><?= $ml ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Reference / Transaction ID</label>
          <input type="text" name="reference" class="form-control" placeholder="Optional">
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="pay_notes" class="form-control" style="min-height:60px"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-payment')">Cancel</button>
        <button type="submit" class="btn btn-primary">Record Payment</button>
      </div>
    </form>
  </div>
</div>


<?php /* ═════════════════════════════════════════════════════════════════
       CREATE / EDIT FORM  — unchanged
       ═════════════════════════════════════════════════════════════════ */
elseif ($edit_inv || isset($_GET['new'])): ?>

<div style="margin-bottom:14px">
  <a href="invoices.php<?= $edit_id ? "?view=$edit_id" : '' ?>" style="color:var(--text3);font-size:13px">
    ← <?= $edit_id ? 'Back to Invoice' : 'All Invoices' ?>
  </a>
</div>

<form method="POST" enctype="multipart/form-data" id="inv-form">
  <input type="hidden" name="action"  value="<?= $edit_id ? 'update_invoice' : 'create_invoice' ?>">
  <input type="hidden" name="inv_id"  value="<?= $edit_id ?>">

  <div style="display:grid;grid-template-columns:2fr 1fr;gap:18px;align-items:start">
    <div>
      <div class="card" style="margin-bottom:14px">
        <div class="card-header"><div class="card-title"><?= $edit_id ? 'Edit Invoice' : 'New Invoice' ?></div></div>

        <div class="form-group">
          <label class="form-label">Invoice Title *</label>
          <input type="text" name="title" class="form-control" required
                 value="<?= h($edit_inv['title'] ?? '') ?>"
                 placeholder="e.g. Web Development — Q2 2026">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Client</label>
            <select name="contact_id" class="form-control">
              <option value="">— None —</option>
              <?php foreach ($contacts as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($edit_inv['contact_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                <?= h($c['name']) ?><?= $c['company'] ? ' · '.h($c['company']) : '' ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Project</label>
            <select name="project_id" class="form-control">
              <option value="">— None —</option>
              <?php foreach ($projects as $p): ?>
              <option value="<?= $p['id'] ?>" <?= ($edit_inv['project_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                <?= h($p['title']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Issue Date</label>
            <input type="date" name="issue_date" class="form-control" value="<?= h($edit_inv['issue_date'] ?? date('Y-m-d')) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Due Date</label>
            <input type="date" name="due_date" class="form-control" value="<?= h($edit_inv['due_date'] ?? '') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Currency</label>
            <select name="currency" class="form-control" id="inv-currency">
              <?php foreach (['LKR'=>'Rs. — LKR','USD'=>'$ — USD','EUR'=>'€ — EUR','GBP'=>'£ — GBP','INR'=>'₹ — INR'] as $cv=>$cl): ?>
              <option value="<?= $cv ?>" <?= ($edit_inv['currency'] ?? 'LKR') === $cv ? 'selected' : '' ?>><?= $cl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <?php foreach (['draft','sent','partial','paid','cancelled'] as $sv): ?>
              <option value="<?= $sv ?>" <?= ($edit_inv['status'] ?? 'draft') === $sv ? 'selected' : '' ?>><?= ucfirst($sv) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:14px">
        <div class="card-header"><div class="card-title">Line Items</div></div>
        <div style="display:grid;grid-template-columns:1fr 80px 110px 100px 36px;gap:6px;margin-bottom:8px;padding:0 4px">
          <span style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase">Description</span>
          <span style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase">Qty</span>
          <span style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase">Unit Price</span>
          <span style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;text-align:right">Amount</span>
          <span></span>
        </div>
        <div id="items-container">
          <?php foreach ($edit_items ?: [['description'=>'','quantity'=>1,'unit_price'=>'','amount'=>'']] as $it): ?>
          <div class="item-editor-row">
            <input type="text" name="item_desc[]"  class="form-control" placeholder="Service or product description" value="<?= h($it['description']) ?>">
            <input type="number" name="item_qty[]" class="form-control" placeholder="1" step="0.01" min="0.01" value="<?= h($it['quantity'] ?? 1) ?>" onchange="calcRow(this)" oninput="calcRow(this)">
            <input type="number" name="item_price[]" class="form-control" placeholder="0.00" step="0.01" min="0" value="<?= h($it['unit_price'] ?? '') ?>" onchange="calcRow(this)" oninput="calcRow(this)">
            <input type="text" name="item_amount[]" class="form-control" readonly value="<?= $it['amount'] ? number_format((float)$it['amount'],2) : '' ?>" style="text-align:right;background:var(--bg4)">
            <button type="button" class="btn btn-danger btn-sm btn-icon" onclick="removeRow(this)" style="padding:6px">✕</button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" onclick="addRow()" style="margin-top:8px">＋ Add Item</button>
      </div>

      <div class="card">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Notes <span style="font-weight:400;text-transform:none;font-size:11px;color:var(--text3)">(visible to client)</span></label>
            <textarea name="notes" class="form-control" style="min-height:70px" placeholder="Payment instructions, thank you note…"><?= h($edit_inv['notes'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Terms &amp; Conditions</label>
            <textarea name="terms" class="form-control" style="min-height:70px" placeholder="Payment within 30 days…"><?= h($edit_inv['terms'] ?? '') ?></textarea>
          </div>
        </div>
        <div style="background:var(--bg3);border-radius:var(--radius-sm);padding:14px;margin-top:8px">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:10px">Override Signature / Stamp for this Invoice</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Signature Image <span style="font-weight:400;text-transform:none;font-size:11px;color:var(--text3)">(PNG/JPG)</span></label>
              <input type="file" name="signature_image" class="form-control" accept="image/*">
              <?php
              $cur_sig = $edit_inv['signature_image'] ?? '';
              $glo_sig = $inv_settings['signature_image'] ?? '';
              if ($cur_sig && file_exists(absPath($cur_sig))): ?>
              <img src="<?= h($cur_sig) ?>" class="img-preview" alt="Current signature">
              <?php elseif ($glo_sig && file_exists(absPath($glo_sig))): ?>
              <div style="font-size:11px;color:var(--text3);margin-top:4px">Using global signature from settings</div>
              <?php else: ?>
              <div style="font-size:11px;color:var(--text3);margin-top:4px">No signature set — configure in Invoice Settings</div>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label class="form-label">Company Stamp <span style="font-weight:400;text-transform:none;font-size:11px;color:var(--text3)">(PNG/JPG)</span></label>
              <input type="file" name="stamp_image" class="form-control" accept="image/*">
              <?php
              $cur_stm = $edit_inv['stamp_image'] ?? '';
              $glo_stm = $inv_settings['stamp_image'] ?? '';
              if ($cur_stm && file_exists(absPath($cur_stm))): ?>
              <img src="<?= h($cur_stm) ?>" class="img-preview" alt="Current stamp">
              <?php elseif ($glo_stm && file_exists(absPath($glo_stm))): ?>
              <div style="font-size:11px;color:var(--text3);margin-top:4px">Using global stamp from settings</div>
              <?php else: ?>
              <div style="font-size:11px;color:var(--text3);margin-top:4px">No stamp set — configure in Invoice Settings</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div>
      <div class="card" style="margin-bottom:14px">
        <div class="card-title" style="margin-bottom:14px">Totals Preview</div>
        <div style="display:flex;flex-direction:column;gap:8px">
          <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border)">
            <span style="font-size:13px;color:var(--text2)">Subtotal</span>
            <span style="font-size:13px;font-weight:700" id="preview-sub">0.00</span>
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Tax Rate (%)</label>
            <input type="number" name="tax_rate" id="tax-rate" class="form-control" step="0.01" min="0" max="100"
                   value="<?= h($edit_inv['tax_rate'] ?? 0) ?>" onchange="updateTotals()" oninput="updateTotals()">
          </div>
          <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border)">
            <span style="font-size:13px;color:var(--text2)">Tax Amount</span>
            <span style="font-size:13px;font-weight:700" id="preview-tax">0.00</span>
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Discount</label>
            <input type="number" name="discount" id="discount" class="form-control" step="0.01" min="0"
                   value="<?= h($edit_inv['discount'] ?? 0) ?>" onchange="updateTotals()" oninput="updateTotals()">
          </div>
          <div style="background:var(--orange-bg);border-radius:var(--radius-sm);padding:10px 12px;display:flex;justify-content:space-between">
            <span style="font-size:14px;font-weight:700;color:var(--orange)">Total</span>
            <span style="font-size:16px;font-weight:800;color:var(--orange)" id="preview-total">0.00</span>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-title" style="margin-bottom:12px">Recurring Invoice</div>
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;margin-bottom:12px">
          <input type="checkbox" name="is_recurring" id="chk-recur"
                 <?= ($edit_inv['is_recurring'] ?? 0) ? 'checked' : '' ?>
                 onchange="document.getElementById('recur-opts').style.display=this.checked?'block':'none'"
                 style="accent-color:var(--orange)">
          Enable recurring invoice
        </label>
        <div id="recur-opts" style="display:<?= ($edit_inv['is_recurring'] ?? 0) ? 'block' : 'none' ?>">
          <div class="form-group">
            <label class="form-label">Interval</label>
            <select name="recur_interval" class="form-control">
              <?php foreach (['monthly'=>'Monthly','quarterly'=>'Quarterly','yearly'=>'Yearly'] as $rv=>$rl): ?>
              <option value="<?= $rv ?>" <?= ($edit_inv['recur_interval'] ?? '') === $rv ? 'selected' : '' ?>><?= $rl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Next Invoice Date</label>
            <input type="date" name="recur_next" class="form-control" value="<?= h($edit_inv['recur_next'] ?? '') ?>">
          </div>
        </div>
      </div>

      <div style="margin-top:14px;display:flex;gap:8px">
        <button type="submit" class="btn btn-primary" style="flex:1">
          <?= $edit_id ? 'Save Changes' : 'Create Invoice' ?>
        </button>
        <a href="invoices.php<?= $edit_id ? "?view=$edit_id" : '' ?>" class="btn btn-ghost">Cancel</a>
      </div>
    </div>
  </div>
</form>


<?php /* ═════════════════════════════════════════════════════════════════
       INVOICE SETTINGS  — unchanged
       ═════════════════════════════════════════════════════════════════ */
elseif ($tab === 'settings' && isAdmin()): ?>

<div style="margin-bottom:14px">
  <a href="invoices.php" style="color:var(--text3);font-size:13px">← All Invoices</a>
</div>
<h2 style="font-family:var(--font-display);font-size:20px;font-weight:700;margin-bottom:20px">⚙ Invoice Settings</h2>

<form method="POST" enctype="multipart/form-data">
  <input type="hidden" name="action" value="save_invoice_settings">

  <div class="settings-section">
    <div class="settings-section-title">Company Information</div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Company Name</label>
        <input type="text" name="company_name" class="form-control" value="<?= h($inv_settings['company_name'] ?? '') ?>" placeholder="Padak Pvt Ltd">
      </div>
      <div class="form-group">
        <label class="form-label">Tagline</label>
        <input type="text" name="company_tagline" class="form-control" value="<?= h($inv_settings['company_tagline'] ?? '') ?>" placeholder="Technology Solutions">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Address</label>
      <textarea name="company_address" class="form-control" style="min-height:70px" placeholder="Street, City, State, Country"><?= h($inv_settings['company_address'] ?? '') ?></textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Phone</label>
        <input type="text" name="company_phone" class="form-control" value="<?= h($inv_settings['company_phone'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="company_email" class="form-control" value="<?= h($inv_settings['company_email'] ?? '') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Company Reg No</label>
        <input type="text" name="company_reg_no" class="form-control" value="<?= h($inv_settings['company_reg_no'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">VAT / GST No</label>
        <input type="text" name="company_vat" class="form-control" value="<?= h($inv_settings['company_vat'] ?? '') ?>">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Company Logo <span style="font-weight:400;text-transform:none;font-size:11px;color:var(--text3)">(displayed on invoices — PNG/JPG, shown in original colors)</span></label>
      <input type="file" name="company_logo" class="form-control" accept="image/*">
      <?php if (!empty($inv_settings['company_logo']) && file_exists(absPath($inv_settings['company_logo']))): ?>
      <img src="<?= h($inv_settings['company_logo']) ?>" class="img-preview" alt="Logo">
      <div style="font-size:11px;color:var(--green);margin-top:4px">✓ Logo uploaded</div>
      <?php else: ?>
      <div style="font-size:11px;color:var(--text3);margin-top:4px">No logo uploaded yet</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="settings-section">
    <div class="settings-section-title">Bank / Payment Details</div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Bank Name</label>
        <input type="text" name="bank_name" class="form-control" value="<?= h($inv_settings['bank_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Account Number</label>
        <input type="text" name="bank_account" class="form-control" value="<?= h($inv_settings['bank_account'] ?? '') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Branch</label>
        <input type="text" name="bank_branch" class="form-control" value="<?= h($inv_settings['bank_branch'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">SWIFT / BIC</label>
        <input type="text" name="bank_swift" class="form-control" value="<?= h($inv_settings['bank_swift'] ?? '') ?>">
      </div>
    </div>
  </div>

  <div class="settings-section">
    <div class="settings-section-title">Signature &amp; Stamp <span style="font-weight:400;text-transform:none;font-size:11px;letter-spacing:0">(Global — applied to all invoices; can be overridden per invoice)</span></div>
    <div style="background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:14px;margin-bottom:14px;font-size:12.5px;color:var(--text2)">
      💡 Use PNG images with transparent background for best results. The signature appears on the left and the stamp on the right of each invoice.
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Authorised Signature <span style="font-weight:400;text-transform:none;font-size:11px;color:var(--text3)">(PNG/JPG — transparent bg recommended)</span></label>
        <input type="file" name="signature_image" class="form-control" accept="image/*">
        <?php if (!empty($inv_settings['signature_image']) && file_exists(absPath($inv_settings['signature_image']))): ?>
        <img src="<?= h($inv_settings['signature_image']) ?>" class="img-preview" alt="Signature">
        <div style="font-size:11px;color:var(--green);margin-top:4px">✓ Signature uploaded</div>
        <?php else: ?>
        <div style="font-size:11px;color:var(--text3);margin-top:4px">No signature uploaded yet</div>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Company Stamp <span style="font-weight:400;text-transform:none;font-size:11px;color:var(--text3)">(PNG/JPG — transparent bg recommended)</span></label>
        <input type="file" name="stamp_image" class="form-control" accept="image/*">
        <?php if (!empty($inv_settings['stamp_image']) && file_exists(absPath($inv_settings['stamp_image']))): ?>
        <img src="<?= h($inv_settings['stamp_image']) ?>" class="img-preview" alt="Stamp">
        <div style="font-size:11px;color:var(--green);margin-top:4px">✓ Stamp uploaded</div>
        <?php else: ?>
        <div style="font-size:11px;color:var(--text3);margin-top:4px">No stamp uploaded yet</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="settings-section">
    <div class="settings-section-title">Invoice Footer</div>
    <div class="form-group">
      <label class="form-label">Footer Text <span style="font-weight:400;text-transform:none;font-size:11px;color:var(--text3)">(shown at bottom of every invoice)</span></label>
      <textarea name="invoice_footer" class="form-control" style="min-height:60px"
                placeholder="Thank you for your business. Payment is due within 30 days."><?= h($inv_settings['invoice_footer'] ?? '') ?></textarea>
    </div>
  </div>

  <div style="display:flex;gap:10px">
    <button type="submit" class="btn btn-primary">Save Settings</button>
    <a href="invoices.php" class="btn btn-ghost">Cancel</a>
  </div>
</form>


<?php /* ═════════════════════════════════════════════════════════════════
       INVOICE LIST  — unchanged
       ═════════════════════════════════════════════════════════════════ */
else: ?>

<div class="inv-tabs">
  <a href="invoices.php" class="inv-tab <?= $tab === 'list' ? 'active' : '' ?>">📋 All Invoices</a>
  <?php if (isAdmin()): ?>
  <a href="invoices.php?tab=settings" class="inv-tab <?= $tab === 'settings' ? 'active' : '' ?>">⚙ Invoice Settings</a>
  <?php endif; ?>
</div>

<div class="inv-stats">
  <?php $sym_d = 'Rs. ';
  $outstanding = max(0, (float)($stats['total_invoiced'] ?? 0) - (float)($stats['total_collected'] ?? 0));
  foreach ([
    ['💰','Total Invoiced', $sym_d.number_format((float)($stats['total_invoiced']??0),2),'rgba(249,115,22,.12)'],
    ['✅','Collected',      $sym_d.number_format((float)($stats['total_collected']??0),2),'rgba(16,185,129,.12)'],
    ['⏳','Outstanding',   $sym_d.number_format($outstanding,2),'rgba(245,158,11,.12)'],
    ['🔴','Overdue',       $sym_d.number_format((float)($stats['total_overdue']??0),2),'rgba(239,68,68,.12)'],
    ['📝','Drafts',        (int)($stats['draft_count']??0).' invoices','rgba(99,102,241,.12)'],
  ] as [$ic,$lb,$vl,$bg]): ?>
  <div class="inv-stat">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
      <div style="width:32px;height:32px;border-radius:8px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;font-size:16px"><?= $ic ?></div>
    </div>
    <div class="inv-stat-val"><?= $vl ?></div>
    <div class="inv-stat-lbl"><?= $lb ?></div>
  </div>
  <?php endforeach; ?>
</div>

<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:16px;flex-wrap:wrap">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;flex:1">
    <div class="search-box" style="min-width:200px;flex:1;max-width:280px">
      <span style="color:var(--text3)">🔍</span>
      <input type="text" name="q" placeholder="Search invoices…" value="<?= h($search) ?>">
    </div>
    <select name="status" class="form-control" style="width:auto" onchange="this.form.submit()">
      <option value="">All Status</option>
      <?php foreach ($allowed_statuses as $sv): ?>
      <option value="<?= $sv ?>" <?= $status_f === $sv ? 'selected' : '' ?>><?= ucfirst($sv) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <?php if (isManager()): ?>
  <a href="invoices.php?new=1" class="btn btn-primary" style="text-decoration:none">＋ New Invoice</a>
  <?php endif; ?>
</div>

<?php if (empty($invoices)): ?>
<div class="card">
  <div class="empty-state">
    <div class="icon">🧾</div>
    <p>No invoices yet.<?= isManager() ? ' <a href="invoices.php?new=1" style="color:var(--orange)">Create the first one</a>' : '' ?></p>
  </div>
</div>
<?php else: ?>
<?php foreach ($invoices as $inv):
  $sc      = invColor($inv['status']);
  $balance = (float)$inv['balance_due'];
?>
<div class="inv-row" onclick="location.href='invoices.php?view=<?= $inv['id'] ?>'">
  <div class="inv-no"><?= h($inv['invoice_no']) ?></div>
  <div class="inv-client">
    <div class="inv-client-name"><?= $inv['client_name'] ? h($inv['client_name']) : h($inv['title']) ?></div>
    <div class="inv-client-sub">
      <?= $inv['project_title'] ? '📁 '.h($inv['project_title']).' · ' : '' ?>
      <?= fDate($inv['issue_date']) ?>
      <?= $inv['due_date'] ? ' · Due '.fDate($inv['due_date']) : '' ?>
    </div>
  </div>
  <div>
    <span class="inv-status" style="background:<?= $sc ?>18;color:<?= $sc ?>"><?= ucfirst($inv['status']) ?></span>
  </div>
  <div class="inv-amount">
    <div class="inv-total-d"><?= invSym($inv['currency']) ?><?= number_format((float)$inv['total'],2) ?></div>
    <?php if ($balance > 0 && $inv['status'] !== 'paid'): ?>
    <div class="inv-balance" style="color:<?= $inv['status'] === 'overdue' ? 'var(--red)' : 'var(--text3)' ?>">
      Due: <?= invSym($inv['currency']) ?><?= number_format($balance,2) ?>
    </div>
    <?php elseif ($inv['status'] === 'paid'): ?>
    <div class="inv-balance" style="color:var(--green)">Paid ✓</div>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php endif; // end routing ?>

<script>
// ── Line item calculator ──────────────────────────────────────────────────────
function calcRow(inp) {
    var row   = inp.closest('.item-editor-row');
    var qty   = parseFloat(row.querySelector('[name="item_qty[]"]').value)   || 0;
    var price = parseFloat(row.querySelector('[name="item_price[]"]').value) || 0;
    row.querySelector('[name="item_amount[]"]').value = (qty * price).toFixed(2);
    updateTotals();
}
function updateTotals() {
    var sub = 0;
    document.querySelectorAll('[name="item_amount[]"]').forEach(function(el){
        sub += parseFloat(el.value) || 0;
    });
    var taxRate = parseFloat(document.getElementById('tax-rate')?.value  || 0);
    var disc    = parseFloat(document.getElementById('discount')?.value  || 0);
    var tax     = sub * (taxRate / 100);
    var total   = sub + tax - disc;
    var el;
    if ((el = document.getElementById('preview-sub')))   el.textContent = sub.toFixed(2);
    if ((el = document.getElementById('preview-tax')))   el.textContent = tax.toFixed(2);
    if ((el = document.getElementById('preview-total'))) el.textContent = total.toFixed(2);
}
function addRow() {
    var c   = document.getElementById('items-container');
    var div = document.createElement('div');
    div.className = 'item-editor-row';
    div.innerHTML =
        '<input type="text"   name="item_desc[]"  class="form-control" placeholder="Service or product description">'
      + '<input type="number" name="item_qty[]"   class="form-control" placeholder="1"    step="0.01" min="0.01" value="1" onchange="calcRow(this)" oninput="calcRow(this)">'
      + '<input type="number" name="item_price[]" class="form-control" placeholder="0.00" step="0.01" min="0"    onchange="calcRow(this)" oninput="calcRow(this)">'
      + '<input type="text"   name="item_amount[]" class="form-control" readonly value="" style="text-align:right;background:var(--bg4)">'
      + '<button type="button" class="btn btn-danger btn-sm btn-icon" onclick="removeRow(this)" style="padding:6px">✕</button>';
    c.appendChild(div);
    div.querySelector('[name="item_desc[]"]').focus();
}
function removeRow(btn) {
    if (document.querySelectorAll('.item-editor-row').length <= 1) return;
    btn.closest('.item-editor-row').remove();
    updateTotals();
}
document.addEventListener('DOMContentLoaded', updateTotals);
</script>

<?php renderLayoutEnd(); ?>