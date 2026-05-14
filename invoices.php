<?php
/**
 * invoices.php — Padak CRM
 * PATCHED: Professional invoice standard, digital signature/stamp, print CSS
 */
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

// Auto-create invoice_settings table + columns
@$db->query("CREATE TABLE IF NOT EXISTS invoice_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(200) DEFAULT NULL, company_tagline VARCHAR(200) DEFAULT NULL,
    company_address TEXT DEFAULT NULL, company_phone VARCHAR(80) DEFAULT NULL,
    company_email VARCHAR(200) DEFAULT NULL, company_reg_no VARCHAR(100) DEFAULT NULL,
    company_vat VARCHAR(100) DEFAULT NULL, company_logo VARCHAR(500) DEFAULT NULL,
    bank_name VARCHAR(200) DEFAULT NULL, bank_account VARCHAR(100) DEFAULT NULL,
    bank_branch VARCHAR(200) DEFAULT NULL, bank_swift VARCHAR(50) DEFAULT NULL,
    signature_image VARCHAR(500) DEFAULT NULL, stamp_image VARCHAR(500) DEFAULT NULL,
    invoice_footer TEXT DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
@$db->query("INSERT IGNORE INTO invoice_settings (id) VALUES (1)");
@$db->query("ALTER TABLE invoices ADD COLUMN IF NOT EXISTS signature_image VARCHAR(500) DEFAULT NULL AFTER terms");
@$db->query("ALTER TABLE invoices ADD COLUMN IF NOT EXISTS stamp_image VARCHAR(500) DEFAULT NULL AFTER signature_image");

if (!isManager()) { flash('Access restricted.','error'); header('Location: dashboard.php'); exit; }

function invSym(string $c): string {
    return match($c) { 'USD'=>'$','EUR'=>'€','GBP'=>'£','INR'=>'₹', default=>'Rs. ' };
}
function nextInvoiceNo(mysqli $db): string {
    $year = (int)date('Y');
    $db->query("INSERT INTO invoice_counter (year,seq) VALUES ($year,1) ON DUPLICATE KEY UPDATE seq=seq+1");
    $row = $db->query("SELECT seq FROM invoice_counter WHERE year=$year")->fetch_assoc();
    return 'INV-'.$year.'-'.str_pad($row['seq'],4,'0',STR_PAD_LEFT);
}
function recalcInvoice(mysqli $db, int $inv_id): void {
    $sub = $db->query("SELECT COALESCE(SUM(amount),0) AS s FROM invoice_items WHERE invoice_id=$inv_id")->fetch_assoc()['s'];
    $inv = $db->query("SELECT tax_rate,discount FROM invoices WHERE id=$inv_id")->fetch_assoc();
    $tax = round($sub * ($inv['tax_rate']/100), 2);
    $total = $sub + $tax - $inv['discount'];
    $db->query("UPDATE invoices SET subtotal=$sub,tax_amount=$tax,total=$total WHERE id=$inv_id");
}
function invColor(string $s): string {
    return match($s) {
        'paid'=>'#10b981','partial'=>'#f59e0b','overdue'=>'#ef4444',
        'sent'=>'#6366f1','viewed'=>'#8b5cf6','draft'=>'#94a3b8','cancelled'=>'#64748b',default=>'#94a3b8'
    };
}

// ── UPLOAD HELPER ──────────────────────────────────────────────────────────
function handleImageUpload(string $field, string $prefix): ?string {
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== 0) return null;
    $f   = $_FILES[$field];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) return null;
    if ($f['size'] > 2*1024*1024) return null;
    $dir = __DIR__.'/uploads/invoice/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $fname = $prefix.'_'.uniqid().'.'.$ext;
    if (move_uploaded_file($f['tmp_name'], $dir.$fname)) return 'uploads/invoice/'.$fname;
    return null;
}

// ── POST HANDLERS ──────────────────────────────────────────────────────────
ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── SAVE INVOICE SETTINGS ──
    if ($action === 'save_invoice_settings' && isAdmin()) {
        $fields = ['company_name','company_tagline','company_address','company_phone',
                   'company_email','company_reg_no','company_vat',
                   'bank_name','bank_account','bank_branch','bank_swift','invoice_footer'];
        $sets = [];
        foreach ($fields as $f) {
            $v = $db->real_escape_string(trim($_POST[$f] ?? ''));
            $sets[] = "$f='$v'";
        }
        // Handle logo upload
        $logo_path = handleImageUpload('company_logo', 'logo');
        if ($logo_path) $sets[] = "company_logo='".$db->real_escape_string($logo_path)."'";

        $sig_path = handleImageUpload('signature_image', 'sig');
        if ($sig_path) $sets[] = "signature_image='".$db->real_escape_string($sig_path)."'";

        $stamp_path = handleImageUpload('stamp_image', 'stamp');
        if ($stamp_path) $sets[] = "stamp_image='".$db->real_escape_string($stamp_path)."'";

        $db->query("UPDATE invoice_settings SET ".implode(',',$sets)." WHERE id=1");
        flash('Invoice settings saved.','success');
        ob_end_clean(); header('Location: invoices.php?tab=settings'); exit;
    }

    // ── SAVE INVOICE ──
    if (in_array($action, ['create_invoice','update_invoice'])) {
        if (!isManager()) { flash('Access denied.','error'); ob_end_clean(); header('Location: invoices.php'); exit; }

        $inv_id   = (int)($_POST['inv_id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        $contact  = (int)($_POST['contact_id'] ?? 0) ?: null;
        $project  = (int)($_POST['project_id'] ?? 0) ?: null;
        $status   = $_POST['status'] ?? 'draft';
        $currency = $_POST['currency'] ?? 'LKR';
        $issue    = $_POST['issue_date'] ?: date('Y-m-d');
        $due      = $_POST['due_date'] ?: null;
        $tax_rate = (float)($_POST['tax_rate'] ?? 0);
        $discount = (float)($_POST['discount'] ?? 0);
        $notes    = trim($_POST['notes'] ?? '');
        $terms    = trim($_POST['terms'] ?? '');
        $recur    = isset($_POST['is_recurring']) ? 1 : 0;
        $recur_int  = $_POST['recur_interval'] ?: null;
        $recur_next = $recur && $_POST['recur_next'] ? $_POST['recur_next'] : null;

        if (!$title) { flash('Title required.','error'); ob_end_clean(); header('Location: invoices.php'); exit; }

        // Handle per-invoice signature/stamp upload
        $sig_path   = handleImageUpload('signature_image', 'sig');
        $stamp_path = handleImageUpload('stamp_image', 'stamp');

        if ($action === 'create_invoice') {
            $inv_no = nextInvoiceNo($db);
            $stmt = $db->prepare("INSERT INTO invoices (invoice_no,title,contact_id,project_id,status,currency,issue_date,due_date,tax_rate,discount,notes,terms,is_recurring,recur_interval,recur_next,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssiissssddssiisi",$inv_no,$title,$contact,$project,$status,$currency,$issue,$due,$tax_rate,$discount,$notes,$terms,$recur,$recur_int,$recur_next,$uid);
            $stmt->execute();
            $inv_id = (int)$db->insert_id;
            if ($sig_path)   $db->query("UPDATE invoices SET signature_image='".$db->real_escape_string($sig_path)."' WHERE id=$inv_id");
            if ($stamp_path) $db->query("UPDATE invoices SET stamp_image='".$db->real_escape_string($stamp_path)."' WHERE id=$inv_id");
            logActivity('created invoice',$title,$inv_id);
        } else {
            $stmt = $db->prepare("UPDATE invoices SET title=?,contact_id=?,project_id=?,status=?,currency=?,issue_date=?,due_date=?,tax_rate=?,discount=?,notes=?,terms=?,is_recurring=?,recur_interval=?,recur_next=? WHERE id=?");
            $stmt->bind_param("siissssddssiisi",$title,$contact,$project,$status,$currency,$issue,$due,$tax_rate,$discount,$notes,$terms,$recur,$recur_int,$recur_next,$inv_id);
            $stmt->execute();
            if ($sig_path)   $db->query("UPDATE invoices SET signature_image='".$db->real_escape_string($sig_path)."' WHERE id=$inv_id");
            if ($stamp_path) $db->query("UPDATE invoices SET stamp_image='".$db->real_escape_string($stamp_path)."' WHERE id=$inv_id");
            logActivity('updated invoice',$title,$inv_id);
        }

        $db->query("DELETE FROM invoice_items WHERE invoice_id=$inv_id");
        $descs = $_POST['item_desc'] ?? []; $qtys = $_POST['item_qty'] ?? []; $prices = $_POST['item_price'] ?? [];
        $si = $db->prepare("INSERT INTO invoice_items (invoice_id,description,quantity,unit_price,amount,sort_order) VALUES (?,?,?,?,?,?)");
        foreach ($descs as $i => $desc) {
            $desc = trim($desc); if (!$desc) continue;
            $qty = max(0.01,(float)($qtys[$i]??1)); $price = max(0,(float)($prices[$i]??0)); $amt = round($qty*$price,2);
            $si->bind_param("isdddi",$inv_id,$desc,$qty,$price,$amt,$i); $si->execute();
        }
        recalcInvoice($db, $inv_id);
        flash('Invoice saved.','success');
        ob_end_clean(); header('Location: invoices.php?view='.$inv_id); exit;
    }

    if ($action === 'delete_invoice' && isManager()) {
        $id = (int)$_POST['inv_id'];
        $inv = $db->query("SELECT title FROM invoices WHERE id=$id")->fetch_assoc();
        $db->query("DELETE FROM invoices WHERE id=$id");
        logActivity('deleted invoice',$inv['title']??'',$id);
        flash('Invoice deleted.','success');
        ob_end_clean(); header('Location: invoices.php'); exit;
    }

    if ($action === 'mark_sent' && isManager()) {
        $id = (int)$_POST['inv_id'];
        $db->query("UPDATE invoices SET status='sent',sent_at=NOW() WHERE id=$id AND status='draft'");
        require_once 'includes/mailer.php';
        $inv_row = $db->query("SELECT i.*,c.name AS cn,c.email AS ce FROM invoices i LEFT JOIN contacts c ON c.id=i.contact_id WHERE i.id=$id")->fetch_assoc();
        if ($inv_row && $inv_row['ce']) {
            $tmpl = $db->query("SELECT body_html FROM email_templates WHERE name='Invoice Sent' LIMIT 1")->fetch_assoc();
            $html = $tmpl ? renderEmailTemplate($tmpl['body_html'],['name'=>$inv_row['cn'],'invoice_no'=>$inv_row['invoice_no'],'amount'=>invSym($inv_row['currency']).number_format($inv_row['total'],2),'due_date'=>$inv_row['due_date']?date('M j, Y',strtotime($inv_row['due_date'])):'On receipt','link'=>(defined('BASE_URL')?BASE_URL:'').'/client_portal.php?page=invoices',]) : '<p>Invoice '.$inv_row['invoice_no'].'</p>';
            $smtp = $db->query("SELECT from_email,from_name FROM email_settings WHERE is_default=1 LIMIT 1")->fetch_assoc();
            sendAndLog(['to'=>[$inv_row['cn'].' <'.$inv_row['ce'].'>'],'subject'=>'Invoice '.$inv_row['invoice_no'].' — '.invSym($inv_row['currency']).number_format($inv_row['total'],2),'html'=>$html,'text'=>strip_tags($html),'from_email'=>$smtp['from_email']??'noreply@thepadak.com','from_name'=>$smtp['from_name']??'Padak','invoice_id'=>$id,'contact_id'=>$inv_row['contact_id'],'sent_by'=>$uid],$db);
        }
        flash('Invoice marked as sent.','success');
        ob_end_clean(); header('Location: invoices.php?view='.$id); exit;
    }

    if ($action === 'record_payment' && isManager()) {
        $inv_id = (int)$_POST['inv_id']; $amount = (float)$_POST['amount'];
        $method = $_POST['method']??'bank_transfer'; $ref = trim($_POST['reference']??'');
        $date = $_POST['paid_at']?:date('Y-m-d'); $notes = trim($_POST['pay_notes']??'');
        $stmt = $db->prepare("INSERT INTO invoice_payments (invoice_id,amount,method,reference,paid_at,notes,recorded_by) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("idssssi",$inv_id,$amount,$method,$ref,$date,$notes,$uid); $stmt->execute();
        $inv = $db->query("SELECT total,amount_paid FROM invoices WHERE id=$inv_id")->fetch_assoc();
        $paid = round($inv['amount_paid']+$amount,2);
        $nstat = $paid>=$inv['total']?'paid':($paid>0?'partial':'sent');
        $paid_at = $paid>=$inv['total']?",paid_at=NOW()":'';
        $db->query("UPDATE invoices SET amount_paid=$paid,status='$nstat'$paid_at WHERE id=$inv_id");
        logActivity('recorded payment','invoice',$inv_id,"Amount: $amount");
        flash('Payment recorded.','success');
        ob_end_clean(); header('Location: invoices.php?view='.$inv_id); exit;
    }
}
ob_end_clean();

// ── LOAD DATA ──────────────────────────────────────────────────────────────
$tab     = $_GET['tab'] ?? 'list';
$view_id = (int)($_GET['view']  ?? 0);
$edit_id = (int)($_GET['edit']  ?? 0);
$status_f= $_GET['status'] ?? '';
$search  = trim($_GET['q'] ?? '');

// Load invoice settings
$inv_settings = $db->query("SELECT * FROM invoice_settings WHERE id=1")->fetch_assoc() ?? [];

$contacts = $db->query("SELECT id,name,company,email,address,phone FROM contacts WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$projects = $db->query("SELECT id,title FROM projects WHERE status NOT IN('cancelled') ORDER BY title")->fetch_all(MYSQLI_ASSOC);

$where = "1=1";
if ($status_f) $where .= " AND i.status='".$db->real_escape_string($status_f)."'";
if ($search)   $where .= " AND (i.invoice_no LIKE '%".$db->real_escape_string($search)."%' OR i.title LIKE '%".$db->real_escape_string($search)."%' OR c.name LIKE '%".$db->real_escape_string($search)."%')";

$invoices = $db->query("
    SELECT i.*, c.name AS client_name, c.company, c.email AS client_email, c.address AS client_address, c.phone AS client_phone,
           p.title AS project_title, COALESCE(i.total - i.amount_paid, 0) AS balance_due
    FROM invoices i
    LEFT JOIN contacts c ON c.id=i.contact_id
    LEFT JOIN projects p ON p.id=i.project_id
    WHERE $where ORDER BY i.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$stats = $db->query("
    SELECT
        SUM(CASE WHEN status NOT IN('cancelled','draft') THEN total ELSE 0 END) AS total_invoiced,
        SUM(amount_paid) AS total_collected,
        SUM(CASE WHEN status='overdue' THEN total-amount_paid ELSE 0 END) AS total_overdue,
        COUNT(CASE WHEN status='draft' THEN 1 END) AS draft_count,
        COUNT(CASE WHEN status='overdue' THEN 1 END) AS overdue_count
    FROM invoices
")->fetch_assoc();

$inv = null; $items = []; $payments = [];
if ($view_id) {
    $inv = $db->query("SELECT i.*,c.name AS client_name,c.company,c.email AS client_email,c.address AS client_address,c.phone AS client_phone,p.title AS project_title FROM invoices i LEFT JOIN contacts c ON c.id=i.contact_id LEFT JOIN projects p ON p.id=i.project_id WHERE i.id=$view_id")->fetch_assoc();
    if ($inv) {
        $items    = $db->query("SELECT * FROM invoice_items WHERE invoice_id=$view_id ORDER BY sort_order,id")->fetch_all(MYSQLI_ASSOC);
        $payments = $db->query("SELECT ip.*,u.name AS recorded_by_name FROM invoice_payments ip JOIN users u ON u.id=ip.recorded_by WHERE invoice_id=$view_id ORDER BY paid_at DESC")->fetch_all(MYSQLI_ASSOC);
        // Mark as viewed if sent
        if ($inv['status'] === 'sent') $db->query("UPDATE invoices SET status='viewed',viewed_at=NOW() WHERE id=$view_id AND status='sent'");
    }
}

$edit_inv = null; $edit_items = [];
if ($edit_id) {
    $edit_inv   = $db->query("SELECT * FROM invoices WHERE id=$edit_id")->fetch_assoc();
    $edit_items = $db->query("SELECT * FROM invoice_items WHERE invoice_id=$edit_id ORDER BY sort_order,id")->fetch_all(MYSQLI_ASSOC);
}

$db->query("UPDATE invoices SET status='overdue' WHERE status='sent' AND due_date < CURDATE() AND due_date IS NOT NULL");

renderLayout('Invoices', 'invoices');
?>

<style>
/* ── INVOICES PAGE ── */
.inv-stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-bottom:20px}
.inv-stat{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px}
.inv-stat-val{font-size:20px;font-weight:800;color:var(--text);margin-bottom:2px;font-family:var(--font-display)}
.inv-stat-lbl{font-size:11.5px;color:var(--text3);text-transform:uppercase;letter-spacing:.05em}
.inv-row{display:flex;align-items:center;gap:14px;padding:13px 16px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:7px;cursor:pointer;transition:border-color .15s,box-shadow .15s}
.inv-row:hover{border-color:var(--border2);box-shadow:0 2px 8px rgba(0,0,0,.15)}
.inv-no{font-size:12px;font-weight:700;color:var(--text3);min-width:100px}
.inv-client{flex:1;min-width:0}
.inv-client-name{font-size:13.5px;font-weight:700;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.inv-client-sub{font-size:11.5px;color:var(--text3)}
.inv-amount{text-align:right;min-width:100px;flex-shrink:0}
.inv-total-d{font-size:14px;font-weight:700;color:var(--text)}
.inv-balance{font-size:11.5px;font-weight:600}
.inv-detail-grid{display:grid;grid-template-columns:1fr 300px;gap:18px;align-items:start}
.inv-header-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;margin-bottom:16px}
.inv-meta-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px}
.inv-meta-box{background:var(--bg3);border-radius:8px;padding:10px 12px}
.inv-meta-lbl{font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px}
.inv-meta-val{font-size:13px;font-weight:600;color:var(--text)}
.items-table{width:100%;border-collapse:collapse;margin-bottom:0}
.items-table th{font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;padding:8px 12px;border-bottom:2px solid var(--border);text-align:left}
.items-table th:last-child,.items-table td:last-child{text-align:right}
.items-table td{padding:10px 12px;border-bottom:1px solid var(--border);font-size:13.5px;color:var(--text);vertical-align:top}
.items-table tr:last-child td{border-bottom:none}
.inv-totals{display:flex;flex-direction:column;gap:4px;margin-top:0}
.inv-total-row{display:flex;justify-content:space-between;padding:6px 12px;font-size:13px}
.inv-total-row.grand{background:var(--orange-bg);border-radius:var(--radius-sm);font-weight:700;font-size:15px;color:var(--orange)}
.inv-total-row.paid-row{color:var(--green);font-weight:600}
.inv-total-row.balance-row{color:var(--red);font-weight:700}
.pay-row{display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--bg3);border-radius:var(--radius-sm);margin-bottom:6px}
.item-editor-row{display:grid;grid-template-columns:1fr 80px 110px 100px 36px;gap:6px;align-items:center;margin-bottom:6px}
.inv-status{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}

/* ── LETTERHEAD / PROFESSIONAL INVOICE ── */
.inv-letterhead{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:24px;padding-bottom:20px;border-bottom:2px solid var(--border)}
.inv-company-block{}
.inv-company-logo{max-height:52px;max-width:180px;object-fit:contain;display:block;margin-bottom:10px;border-radius:4px}
.inv-company-name{font-family:var(--font-display);font-size:18px;font-weight:800;color:var(--text);margin-bottom:2px}
.inv-company-detail{font-size:12px;color:var(--text3);line-height:1.7}
.inv-to-from{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;padding:16px;background:var(--bg3);border-radius:var(--radius)}
.inv-party-label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--orange);margin-bottom:8px}
.inv-party-name{font-size:14px;font-weight:700;color:var(--text);margin-bottom:3px}
.inv-party-detail{font-size:12px;color:var(--text3);line-height:1.7}

/* ── SIGNATURE & STAMP ── */
.inv-sign-section{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-top:28px;padding-top:20px;border-top:1px solid var(--border)}
.inv-sign-block{text-align:center}
.inv-sign-img-wrap{height:60px;display:flex;align-items:flex-end;justify-content:center;margin-bottom:8px}
.inv-sign-img{max-height:56px;max-width:160px;object-fit:contain;display:block}
.inv-sign-line{border-top:1.5px solid var(--border2);padding-top:6px;font-size:11px;color:var(--text3);text-align:center}
.inv-sign-name{font-size:12.5px;font-weight:700;color:var(--text);text-align:center;margin-top:3px}

/* ── BANK DETAILS ── */
.inv-bank-box{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;margin-top:16px}
.inv-bank-title{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--text3);margin-bottom:10px}
.inv-bank-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px}
.inv-bank-row{display:flex;gap:6px;font-size:12px}
.inv-bank-lbl{color:var(--text3);min-width:90px;flex-shrink:0}
.inv-bank-val{color:var(--text);font-weight:600}

/* ── SETTINGS FORM ── */
.settings-section{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:22px;margin-bottom:16px}
.settings-section-title{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--text3);padding-bottom:12px;border-bottom:1px solid var(--border);margin-bottom:18px}
.img-preview{max-height:60px;max-width:180px;object-fit:contain;border-radius:4px;margin-top:8px;display:block}

/* ── TABS ── */
.inv-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:20px}
.inv-tab{padding:10px 18px;font-size:13px;font-weight:600;color:var(--text3);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s;text-decoration:none;white-space:nowrap}
.inv-tab:hover,.inv-tab.active{color:var(--orange);border-bottom-color:var(--orange)}

/* ── PRINT STYLES ── */
@media print {
    @page { size: A4; margin: 15mm 18mm; }
    body { background: #fff !important; color: #000 !important; font-size: 11pt; }
    .nav-sidebar, .topbar, .page-header,
    [style*="display:flex;gap:8px;flex-wrap:wrap"],
    .modal-overlay, .btn, button, a.btn { display: none !important; }
    .inv-detail-grid { display: block !important; }
    .inv-header-card { box-shadow: none !important; border: none !important; padding: 0 !important; }
    .inv-letterhead { border-bottom: 2px solid #333 !important; }
    .items-table th { background: #f5f5f5 !important; color: #333 !important; }
    .items-table td { color: #333 !important; border-bottom: 1px solid #ddd !important; }
    .inv-total-row.grand { background: #fff8f0 !important; color: #c45a00 !important; }
    .inv-to-from { background: #f8f8f8 !important; }
    .inv-meta-box { background: #f5f5f5 !important; }
    .inv-bank-box { background: #f5f5f5 !important; border: 1px solid #ddd !important; }
    .inv-sign-section { page-break-inside: avoid; }
}

@media(max-width:960px){.inv-detail-grid{grid-template-columns:1fr}.inv-meta-grid{grid-template-columns:1fr 1fr}}
@media(max-width:600px){.inv-meta-grid{grid-template-columns:1fr 1fr}.item-editor-row{grid-template-columns:1fr 70px 90px 80px 30px}.inv-stats{grid-template-columns:1fr 1fr}.inv-to-from{grid-template-columns:1fr}.inv-sign-section{grid-template-columns:1fr}}
</style>

<?php if ($inv): // ══ SINGLE INVOICE VIEW ══
    $sym = invSym($inv['currency']);
    $sc  = invColor($inv['status']);
    $co  = $inv_settings;
    // Resolve signature: invoice-specific overrides global setting
    $sig_path   = !empty($inv['signature_image'])   ? $inv['signature_image']   : ($co['signature_image'] ?? '');
    $stamp_path = !empty($inv['stamp_image'])        ? $inv['stamp_image']       : ($co['stamp_image'] ?? '');
    $logo_path  = $co['company_logo'] ?? '';
?>

<div style="margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
  <a href="invoices.php" style="color:var(--text3);font-size:13px">← All Invoices</a>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <?php if ($inv['status']==='draft' && isManager()): ?>
    <form method="POST" style="display:inline"><input type="hidden" name="action" value="mark_sent"><input type="hidden" name="inv_id" value="<?= $view_id ?>"><button class="btn btn-ghost btn-sm">📤 Mark Sent</button></form>
    <?php endif; ?>
    <?php if (in_array($inv['status'],['sent','viewed','partial','overdue']) && isManager()): ?>
    <button class="btn btn-primary btn-sm" onclick="openModal('modal-payment')">💳 Record Payment</button>
    <?php endif; ?>
    <?php if (isManager()): ?>
    <a href="invoices.php?edit=<?= $view_id ?>" class="btn btn-ghost btn-sm">✎ Edit</a>
    <button class="btn btn-ghost btn-sm" onclick="window.print()">🖨 Print / PDF</button>
    <form method="POST" onsubmit="return confirm('Delete invoice?')" style="display:inline"><input type="hidden" name="action" value="delete_invoice"><input type="hidden" name="inv_id" value="<?= $view_id ?>"><button class="btn btn-danger btn-sm">🗑</button></form>
    <?php endif; ?>
  </div>
</div>

<div class="inv-detail-grid">
  <div>
    <div class="inv-header-card">

      <!-- ── COMPANY LETTERHEAD ── -->
      <div class="inv-letterhead">
        <div class="inv-company-block">
          <?php if ($logo_path && file_exists($logo_path)): ?>
          <img src="<?= h($logo_path) ?>" class="inv-company-logo" alt="Logo">
          <?php endif; ?>
          <div class="inv-company-name"><?= h($co['company_name'] ?? 'Padak') ?></div>
          <?php if (!empty($co['company_tagline'])): ?><div style="font-size:12px;color:var(--text3);margin-bottom:4px"><?= h($co['company_tagline']) ?></div><?php endif; ?>
          <div class="inv-company-detail">
            <?php if (!empty($co['company_address'])): ?><?= nl2br(h($co['company_address'])) ?><br><?php endif; ?>
            <?php if (!empty($co['company_phone'])): ?>📞 <?= h($co['company_phone']) ?><br><?php endif; ?>
            <?php if (!empty($co['company_email'])): ?>✉ <?= h($co['company_email']) ?><br><?php endif; ?>
            <?php if (!empty($co['company_reg_no'])): ?>Reg: <?= h($co['company_reg_no']) ?><?php endif; ?>
            <?php if (!empty($co['company_vat'])): ?> · VAT: <?= h($co['company_vat']) ?><?php endif; ?>
          </div>
        </div>
        <div style="text-align:right;flex-shrink:0">
          <div style="font-size:32px;font-weight:900;color:var(--orange);font-family:var(--font-display);letter-spacing:2px">INVOICE</div>
          <div style="font-size:15px;font-weight:700;color:var(--text3);margin-top:4px"><?= h($inv['invoice_no']) ?></div>
          <span class="inv-status" style="background:<?= $sc ?>18;color:<?= $sc ?>;margin-top:8px"><?= ucfirst($inv['status']) ?></span>
          <div style="font-size:28px;font-weight:800;color:var(--orange);font-family:var(--font-display);margin-top:12px"><?= $sym ?><?= number_format($inv['total'],2) ?></div>
          <?php if ($inv['amount_paid'] > 0 && $inv['status'] !== 'paid'): ?>
          <div style="font-size:13px;color:var(--red);font-weight:600">Balance Due: <?= $sym ?><?= number_format($inv['total']-$inv['amount_paid'],2) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ── FROM / TO ── -->
      <div class="inv-to-from">
        <div>
          <div class="inv-party-label">From</div>
          <div class="inv-party-name"><?= h($co['company_name'] ?? 'Padak') ?></div>
          <?php if (!empty($co['company_address'])): ?><div class="inv-party-detail"><?= nl2br(h($co['company_address'])) ?></div><?php endif; ?>
          <?php if (!empty($co['company_email'])): ?><div class="inv-party-detail"><?= h($co['company_email']) ?></div><?php endif; ?>
          <?php if (!empty($co['company_reg_no'])): ?><div class="inv-party-detail">Reg: <?= h($co['company_reg_no']) ?></div><?php endif; ?>
        </div>
        <div>
          <div class="inv-party-label">Bill To</div>
          <div class="inv-party-name"><?= h($inv['client_name'] ?? '—') ?></div>
          <?php if ($inv['company']): ?><div class="inv-party-detail" style="font-weight:600"><?= h($inv['company']) ?></div><?php endif; ?>
          <?php if ($inv['client_address']): ?><div class="inv-party-detail"><?= nl2br(h($inv['client_address'])) ?></div><?php endif; ?>
          <?php if ($inv['client_email']): ?><div class="inv-party-detail"><?= h($inv['client_email']) ?></div><?php endif; ?>
          <?php if ($inv['client_phone']): ?><div class="inv-party-detail"><?= h($inv['client_phone']) ?></div><?php endif; ?>
        </div>
      </div>

      <!-- ── META GRID ── -->
      <div class="inv-meta-grid">
        <div class="inv-meta-box"><div class="inv-meta-lbl">Invoice No</div><div class="inv-meta-val"><?= h($inv['invoice_no']) ?></div></div>
        <div class="inv-meta-box"><div class="inv-meta-lbl">Issue Date</div><div class="inv-meta-val"><?= fDate($inv['issue_date']) ?></div></div>
        <div class="inv-meta-box"><div class="inv-meta-lbl">Due Date</div><div class="inv-meta-val" <?= ($inv['due_date']&&$inv['due_date']<date('Y-m-d')&&$inv['status']!='paid')?'style="color:var(--red)"':'' ?>><?= fDate($inv['due_date']) ?: '—' ?></div></div>
        <div class="inv-meta-box"><div class="inv-meta-lbl">Project</div><div class="inv-meta-val"><?= $inv['project_title'] ? h($inv['project_title']) : '—' ?></div></div>
      </div>

      <!-- ── LINE ITEMS ── -->
      <div style="border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:16px">
        <table class="items-table">
          <thead><tr>
            <th style="width:50%">Description</th>
            <th style="text-align:right">Qty</th>
            <th style="text-align:right">Unit Price</th>
            <th style="text-align:right">Amount</th>
          </tr></thead>
          <tbody>
          <?php foreach ($items as $idx => $it): ?>
          <tr style="<?= $idx%2==1?'background:var(--bg3)':'' ?>">
            <td><?= h($it['description']) ?></td>
            <td style="text-align:right;color:var(--text2)"><?= rtrim(rtrim(number_format($it['quantity'],2),'0'),'.') ?></td>
            <td style="text-align:right;color:var(--text2)"><?= $sym ?><?= number_format($it['unit_price'],2) ?></td>
            <td style="font-weight:600"><?= $sym ?><?= number_format($it['amount'],2) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- ── TOTALS ── -->
      <div style="display:flex;justify-content:flex-end">
        <div style="min-width:300px">
          <div class="inv-totals">
            <div class="inv-total-row"><span>Subtotal</span><span><?= $sym ?><?= number_format($inv['subtotal'],2) ?></span></div>
            <?php if ($inv['tax_rate'] > 0): ?>
            <div class="inv-total-row"><span>Tax (<?= $inv['tax_rate'] ?>%)</span><span><?= $sym ?><?= number_format($inv['tax_amount'],2) ?></span></div>
            <?php endif; ?>
            <?php if ($inv['discount'] > 0): ?>
            <div class="inv-total-row" style="color:var(--green)"><span>Discount</span><span>−<?= $sym ?><?= number_format($inv['discount'],2) ?></span></div>
            <?php endif; ?>
            <div class="inv-total-row grand"><span>Total</span><span><?= $sym ?><?= number_format($inv['total'],2) ?></span></div>
            <?php if ($inv['amount_paid'] > 0): ?>
            <div class="inv-total-row paid-row"><span>Amount Paid</span><span><?= $sym ?><?= number_format($inv['amount_paid'],2) ?></span></div>
            <?php if ($inv['status'] !== 'paid'): ?>
            <div class="inv-total-row balance-row"><span>Balance Due</span><span><?= $sym ?><?= number_format($inv['total']-$inv['amount_paid'],2) ?></span></div>
            <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- ── BANK DETAILS ── -->
      <?php if (!empty($co['bank_name']) || !empty($co['bank_account'])): ?>
      <div class="inv-bank-box">
        <div class="inv-bank-title">💳 Payment / Bank Details</div>
        <div class="inv-bank-grid">
          <?php foreach ([
            ['Bank Name', $co['bank_name'] ?? ''],
            ['Account No', $co['bank_account'] ?? ''],
            ['Branch', $co['bank_branch'] ?? ''],
            ['SWIFT/BIC', $co['bank_swift'] ?? ''],
          ] as [$lbl, $val]): if (!$val) continue; ?>
          <div class="inv-bank-row"><span class="inv-bank-lbl"><?= $lbl ?></span><span class="inv-bank-val"><?= h($val) ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- ── NOTES & TERMS ── -->
      <?php if ($inv['notes']): ?>
      <div style="margin-top:16px;padding:12px;background:var(--bg3);border-radius:var(--radius-sm)">
        <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:6px">Notes</div>
        <div style="font-size:13px;color:var(--text2)"><?= nl2br(h($inv['notes'])) ?></div>
      </div>
      <?php endif; ?>
      <?php if ($inv['terms']): ?>
      <div style="margin-top:8px;padding:12px;background:var(--bg3);border-radius:var(--radius-sm)">
        <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:6px">Terms &amp; Conditions</div>
        <div style="font-size:12.5px;color:var(--text3)"><?= nl2br(h($inv['terms'])) ?></div>
      </div>
      <?php endif; ?>

      <!-- ── SIGNATURE & STAMP SECTION ── -->
      <div class="inv-sign-section">
        <!-- Prepared by -->
        <div class="inv-sign-block">
          <div class="inv-sign-img-wrap"><div style="height:56px"></div></div>
          <div class="inv-sign-line">Prepared by: Accounts / Finance</div>
          <div class="inv-sign-name"><?= h($co['company_name'] ?? 'Padak') ?></div>
        </div>
        <!-- Authorised signature -->
        <div class="inv-sign-block">
          <div class="inv-sign-img-wrap">
            <?php if ($sig_path && file_exists($sig_path)): ?>
            <img src="<?= h($sig_path) ?>" class="inv-sign-img" alt="Signature">
            <?php else: ?>
            <div style="height:56px;border-bottom:1.5px dashed var(--border2);width:140px"></div>
            <?php endif; ?>
          </div>
          <div class="inv-sign-line">Authorised Signatory</div>
          <div class="inv-sign-name"><?= h($co['company_name'] ?? '') ?></div>
        </div>
        <!-- Company stamp -->
        <div class="inv-sign-block">
          <div class="inv-sign-img-wrap">
            <?php if ($stamp_path && file_exists($stamp_path)): ?>
            <img src="<?= h($stamp_path) ?>" class="inv-sign-img" alt="Stamp">
            <?php else: ?>
            <div style="width:64px;height:64px;border:2px dashed var(--border2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;color:var(--text3)">STAMP</div>
            <?php endif; ?>
          </div>
          <div class="inv-sign-line">Company Stamp</div>
          <div class="inv-sign-name"><?= !empty($co['company_reg_no']) ? 'Reg: '.h($co['company_reg_no']) : '' ?></div>
        </div>
      </div>

      <!-- ── FOOTER ── -->
      <?php if (!empty($co['invoice_footer'])): ?>
      <div style="margin-top:20px;padding:12px;background:var(--bg3);border-radius:var(--radius-sm);text-align:center;font-size:12px;color:var(--text3)"><?= h($co['invoice_footer']) ?></div>
      <?php endif; ?>
      <div style="margin-top:10px;text-align:center;font-size:11px;color:var(--text3)">This is a computer-generated invoice. · <?= h($inv['invoice_no']) ?> · <?= date('Y') ?></div>

    </div><!-- end inv-header-card -->

    <!-- Payment history -->
    <?php if ($payments): ?>
    <div class="card">
      <div class="card-header"><div class="card-title">Payment History</div></div>
      <?php foreach ($payments as $py):
        $mc = ['bank_transfer'=>'🏦','cash'=>'💵','card'=>'💳','cheque'=>'📄','online'=>'🌐','other'=>'💰'][$py['method']] ?? '💰';
      ?>
      <div class="pay-row">
        <span style="font-size:20px"><?= $mc ?></span>
        <div style="flex:1"><div style="font-size:13px;font-weight:600;color:var(--green)"><?= $sym ?><?= number_format($py['amount'],2) ?></div><div style="font-size:11.5px;color:var(--text3)"><?= ucfirst(str_replace('_',' ',$py['method'])) ?><?= $py['reference']?' · Ref: '.h($py['reference']):'' ?> · <?= h($py['recorded_by_name']) ?></div></div>
        <div style="font-size:12px;color:var(--text3)"><?= fDate($py['paid_at']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div><!-- end left col -->

  <!-- RIGHT SIDEBAR -->
  <div>
    <div class="card" style="margin-bottom:14px">
      <div class="card-title" style="margin-bottom:12px">Invoice Info</div>
      <?php foreach ([
        ['Status',    '<span class="inv-status" style="background:'.invColor($inv['status']).'18;color:'.invColor($inv['status']).'">'.ucfirst($inv['status']).'</span>'],
        ['Recurring', $inv['is_recurring'] ? '🔁 '.ucfirst($inv['recur_interval']??'').' · Next: '.fDate($inv['recur_next']) : 'No'],
        ['Sent',      $inv['sent_at']   ? fDate($inv['sent_at'],'M j, Y g:ia')   : '—'],
        ['Viewed',    $inv['viewed_at'] ? fDate($inv['viewed_at'],'M j, Y g:ia') : '—'],
        ['Paid',      $inv['paid_at']   ? fDate($inv['paid_at'],'M j, Y')        : '—'],
      ] as [$l,$v]): ?>
      <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border);font-size:12.5px">
        <span style="color:var(--text3)"><?= $l ?></span>
        <span style="color:var(--text2);font-weight:600;text-align:right;max-width:160px"><?= $v ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($inv['client_email'] || $inv['client_name']): ?>
    <div class="card">
      <div class="card-title" style="margin-bottom:12px">Client Contact</div>
      <div style="font-size:13px;font-weight:600;color:var(--text)"><?= h($inv['client_name']) ?></div>
      <?php if ($inv['company']): ?><div style="font-size:12px;color:var(--text3)"><?= h($inv['company']) ?></div><?php endif; ?>
      <?php if ($inv['client_email']): ?><a href="mailto:<?= h($inv['client_email']) ?>" style="font-size:12px;color:var(--orange)"><?= h($inv['client_email']) ?></a><?php endif; ?>
      <?php if ($inv['client_address']): ?><div style="font-size:11.5px;color:var(--text3);margin-top:6px"><?= nl2br(h($inv['client_address'])) ?></div><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (isAdmin()): ?>
    <div class="card" style="margin-top:14px;border-color:var(--orange);background:var(--orange-bg)">
      <div class="card-title" style="margin-bottom:12px;color:var(--orange)">⚙ Invoice Settings</div>
      <p style="font-size:12px;color:var(--text2);margin-bottom:10px">Configure company logo, signature, stamp and bank details for all invoices.</p>
      <a href="invoices.php?tab=settings" class="btn btn-ghost btn-sm" style="text-decoration:none">Open Settings →</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- PAYMENT MODAL -->
<div class="modal-overlay" id="modal-payment">
  <div class="modal" style="max-width:460px">
    <div class="modal-header"><div class="modal-title">💳 Record Payment</div><button class="modal-close" onclick="closeModal('modal-payment')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="record_payment">
      <input type="hidden" name="inv_id" value="<?= $view_id ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Amount *</label><input type="number" name="amount" class="form-control" step="0.01" min="0.01" required value="<?= number_format($inv['total']-$inv['amount_paid'],2) ?>"></div>
          <div class="form-group"><label class="form-label">Date</label><input type="date" name="paid_at" class="form-control" value="<?= date('Y-m-d') ?>"></div>
        </div>
        <div class="form-group"><label class="form-label">Method</label>
          <select name="method" class="form-control">
            <?php foreach (['bank_transfer'=>'Bank Transfer','cash'=>'Cash','card'=>'Card/Cheque','online'=>'Online Payment','other'=>'Other'] as $mv=>$ml): ?>
            <option value="<?= $mv ?>"><?= $ml ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Reference / Transaction ID</label><input type="text" name="reference" class="form-control" placeholder="Optional"></div>
        <div class="form-group"><label class="form-label">Notes</label><textarea name="pay_notes" class="form-control" style="min-height:60px"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('modal-payment')">Cancel</button><button type="submit" class="btn btn-primary">Record Payment</button></div>
    </form>
  </div>
</div>

<?php elseif ($edit_inv || isset($_GET['new'])): // ══ CREATE / EDIT FORM ══ ?>

<div style="margin-bottom:14px"><a href="invoices.php<?= $edit_id?"?view=$edit_id":'' ?>" style="color:var(--text3);font-size:13px">← <?= $edit_id?'Back to Invoice':'All Invoices' ?></a></div>

<form method="POST" enctype="multipart/form-data" id="inv-form">
  <input type="hidden" name="action" value="<?= $edit_id?'update_invoice':'create_invoice' ?>">
  <input type="hidden" name="inv_id" value="<?= $edit_id ?>">
  <div style="display:grid;grid-template-columns:2fr 1fr;gap:18px;align-items:start">
    <div>
      <div class="card" style="margin-bottom:14px">
        <div class="card-header"><div class="card-title"><?= $edit_id?'Edit Invoice':'New Invoice' ?></div></div>
        <div class="form-group"><label class="form-label">Invoice Title *</label><input type="text" name="title" class="form-control" required value="<?= h($edit_inv['title']??'') ?>" placeholder="e.g. Web Development - Q2 2026"></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Client</label>
            <select name="contact_id" class="form-control">
              <option value="">— None —</option>
              <?php foreach ($contacts as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($edit_inv['contact_id']??'')==$c['id']?'selected':'' ?>><?= h($c['name']) ?><?= $c['company']?' · '.h($c['company']):'' ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Project</label>
            <select name="project_id" class="form-control">
              <option value="">— None —</option>
              <?php foreach ($projects as $p): ?>
              <option value="<?= $p['id'] ?>" <?= ($edit_inv['project_id']??'')==$p['id']?'selected':'' ?>><?= h($p['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Issue Date</label><input type="date" name="issue_date" class="form-control" value="<?= h($edit_inv['issue_date']??date('Y-m-d')) ?>"></div>
          <div class="form-group"><label class="form-label">Due Date</label><input type="date" name="due_date" class="form-control" value="<?= h($edit_inv['due_date']??'') ?>"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Currency</label>
            <select name="currency" class="form-control" id="inv-currency">
              <?php foreach (['LKR'=>'Rs. — LKR','USD'=>'$ — USD','EUR'=>'€ — EUR','GBP'=>'£ — GBP','INR'=>'₹ — INR'] as $cv=>$cl): ?>
              <option value="<?= $cv ?>" <?= ($edit_inv['currency']??'LKR')===$cv?'selected':'' ?>><?= $cl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Status</label>
            <select name="status" class="form-control">
              <?php foreach (['draft','sent','partial','paid','cancelled'] as $sv): ?>
              <option value="<?= $sv ?>" <?= ($edit_inv['status']??'draft')===$sv?'selected':'' ?>><?= ucfirst($sv) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- LINE ITEMS -->
      <div class="card" style="margin-bottom:14px">
        <div class="card-header"><div class="card-title">Line Items</div></div>
        <div style="display:grid;grid-template-columns:1fr 80px 110px 100px 36px;gap:6px;margin-bottom:6px;padding:0 4px">
          <span style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase">Description</span>
          <span style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase">Qty</span>
          <span style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase">Unit Price</span>
          <span style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;text-align:right">Amount</span>
          <span></span>
        </div>
        <div id="items-container">
          <?php foreach ($edit_items ?: [['description'=>'','quantity'=>1,'unit_price'=>'','amount'=>'']] as $it): ?>
          <div class="item-editor-row">
            <input type="text" name="item_desc[]" class="form-control" placeholder="Service or product description" value="<?= h($it['description']) ?>">
            <input type="number" name="item_qty[]" class="form-control" placeholder="1" step="0.01" min="0.01" value="<?= h($it['quantity']??1) ?>" onchange="calcRow(this)" oninput="calcRow(this)">
            <input type="number" name="item_price[]" class="form-control" placeholder="0.00" step="0.01" min="0" value="<?= h($it['unit_price']??'') ?>" onchange="calcRow(this)" oninput="calcRow(this)">
            <input type="text" name="item_amount[]" class="form-control" readonly value="<?= $it['amount']?number_format($it['amount'],2):'' ?>" style="text-align:right;background:var(--bg4)">
            <button type="button" class="btn btn-danger btn-sm btn-icon" onclick="removeRow(this)" style="padding:6px">✕</button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" onclick="addRow()" style="margin-top:8px">＋ Add Item</button>
      </div>

      <!-- NOTES, TERMS, SIGNATURE -->
      <div class="card">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Notes (visible to client)</label><textarea name="notes" class="form-control" style="min-height:70px" placeholder="Payment instructions…"><?= h($edit_inv['notes']??'') ?></textarea></div>
          <div class="form-group"><label class="form-label">Terms &amp; Conditions</label><textarea name="terms" class="form-control" style="min-height:70px" placeholder="Payment within 30 days…"><?= h($edit_inv['terms']??'') ?></textarea></div>
        </div>
        <div style="background:var(--bg3);border-radius:var(--radius-sm);padding:14px;margin-top:8px">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:10px">Override Signature / Stamp for this Invoice</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Signature Image (PNG/JPG)</label>
              <input type="file" name="signature_image" class="form-control" accept="image/*">
              <?php if (!empty($edit_inv['signature_image']) && file_exists($edit_inv['signature_image'])): ?>
              <img src="<?= h($edit_inv['signature_image']) ?>" class="img-preview" alt="Current sig">
              <?php elseif (!empty($inv_settings['signature_image']) && file_exists($inv_settings['signature_image'])): ?>
              <div style="font-size:11px;color:var(--text3);margin-top:4px">Using global signature from settings</div>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label class="form-label">Company Stamp (PNG/JPG)</label>
              <input type="file" name="stamp_image" class="form-control" accept="image/*">
              <?php if (!empty($edit_inv['stamp_image']) && file_exists($edit_inv['stamp_image'])): ?>
              <img src="<?= h($edit_inv['stamp_image']) ?>" class="img-preview" alt="Current stamp">
              <?php elseif (!empty($inv_settings['stamp_image']) && file_exists($inv_settings['stamp_image'])): ?>
              <div style="font-size:11px;color:var(--text3);margin-top:4px">Using global stamp from settings</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT SIDEBAR -->
    <div>
      <div class="card" style="margin-bottom:14px">
        <div class="card-title" style="margin-bottom:14px">Totals</div>
        <div style="display:flex;flex-direction:column;gap:8px">
          <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border)"><span style="font-size:13px;color:var(--text2)">Subtotal</span><span style="font-size:13px;font-weight:700" id="preview-sub">0.00</span></div>
          <div class="form-group" style="margin:0"><label class="form-label">Tax Rate (%)</label><input type="number" name="tax_rate" id="tax-rate" class="form-control" step="0.01" min="0" max="100" value="<?= h($edit_inv['tax_rate']??0) ?>" onchange="updateTotals()" oninput="updateTotals()"></div>
          <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border)"><span style="font-size:13px;color:var(--text2)">Tax Amount</span><span style="font-size:13px;font-weight:700" id="preview-tax">0.00</span></div>
          <div class="form-group" style="margin:0"><label class="form-label">Discount</label><input type="number" name="discount" id="discount" class="form-control" step="0.01" min="0" value="<?= h($edit_inv['discount']??0) ?>" onchange="updateTotals()" oninput="updateTotals()"></div>
          <div style="background:var(--orange-bg);border-radius:var(--radius-sm);padding:10px 12px;display:flex;justify-content:space-between">
            <span style="font-size:14px;font-weight:700;color:var(--orange)">Total</span>
            <span style="font-size:16px;font-weight:800;color:var(--orange)" id="preview-total">0.00</span>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-title" style="margin-bottom:12px">Recurring</div>
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;margin-bottom:12px">
          <input type="checkbox" name="is_recurring" id="chk-recur" <?= ($edit_inv['is_recurring']??0)?'checked':'' ?> onchange="document.getElementById('recur-opts').style.display=this.checked?'block':'none'" style="accent-color:var(--orange)">
          Enable recurring invoice
        </label>
        <div id="recur-opts" style="display:<?= ($edit_inv['is_recurring']??0)?'block':'none' ?>">
          <div class="form-group"><label class="form-label">Interval</label>
            <select name="recur_interval" class="form-control">
              <?php foreach (['monthly'=>'Monthly','quarterly'=>'Quarterly','yearly'=>'Yearly'] as $rv=>$rl): ?>
              <option value="<?= $rv ?>" <?= ($edit_inv['recur_interval']??'')===$rv?'selected':'' ?>><?= $rl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Next Invoice Date</label><input type="date" name="recur_next" class="form-control" value="<?= h($edit_inv['recur_next']??'') ?>"></div>
        </div>
      </div>
      <div style="margin-top:14px;display:flex;gap:8px">
        <button type="submit" class="btn btn-primary" style="flex:1"><?= $edit_id?'Save Changes':'Create Invoice' ?></button>
        <a href="invoices.php<?= $edit_id?"?view=$edit_id":'' ?>" class="btn btn-ghost">Cancel</a>
      </div>
    </div>
  </div>
</form>

<?php elseif ($tab === 'settings' && isAdmin()): // ══ SETTINGS ══ ?>

<div style="margin-bottom:14px"><a href="invoices.php" style="color:var(--text3);font-size:13px">← All Invoices</a></div>
<h2 style="font-family:var(--font-display);font-size:20px;font-weight:700;margin-bottom:20px">⚙ Invoice Settings</h2>

<form method="POST" enctype="multipart/form-data">
  <input type="hidden" name="action" value="save_invoice_settings">

  <div class="settings-section">
    <div class="settings-section-title">Company Information</div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Company Name</label><input type="text" name="company_name" class="form-control" value="<?= h($inv_settings['company_name']??'') ?>" placeholder="Padak Pvt Ltd"></div>
      <div class="form-group"><label class="form-label">Tagline</label><input type="text" name="company_tagline" class="form-control" value="<?= h($inv_settings['company_tagline']??'') ?>" placeholder="Technology Solutions"></div>
    </div>
    <div class="form-group"><label class="form-label">Address</label><textarea name="company_address" class="form-control" style="min-height:70px" placeholder="Street, City, State, Country"><?= h($inv_settings['company_address']??'') ?></textarea></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Phone</label><input type="text" name="company_phone" class="form-control" value="<?= h($inv_settings['company_phone']??'') ?>"></div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="company_email" class="form-control" value="<?= h($inv_settings['company_email']??'') ?>"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Company Reg No</label><input type="text" name="company_reg_no" class="form-control" value="<?= h($inv_settings['company_reg_no']??'') ?>"></div>
      <div class="form-group"><label class="form-label">VAT / GST No</label><input type="text" name="company_vat" class="form-control" value="<?= h($inv_settings['company_vat']??'') ?>"></div>
    </div>
    <div class="form-group">
      <label class="form-label">Company Logo (PNG/JPG — displayed on invoices)</label>
      <input type="file" name="company_logo" class="form-control" accept="image/*">
      <?php if (!empty($inv_settings['company_logo']) && file_exists($inv_settings['company_logo'])): ?>
      <img src="<?= h($inv_settings['company_logo']) ?>" class="img-preview" alt="Logo">
      <?php endif; ?>
    </div>
  </div>

  <div class="settings-section">
    <div class="settings-section-title">Bank / Payment Details</div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Bank Name</label><input type="text" name="bank_name" class="form-control" value="<?= h($inv_settings['bank_name']??'') ?>"></div>
      <div class="form-group"><label class="form-label">Account Number</label><input type="text" name="bank_account" class="form-control" value="<?= h($inv_settings['bank_account']??'') ?>"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Branch</label><input type="text" name="bank_branch" class="form-control" value="<?= h($inv_settings['bank_branch']??'') ?>"></div>
      <div class="form-group"><label class="form-label">SWIFT / BIC</label><input type="text" name="bank_swift" class="form-control" value="<?= h($inv_settings['bank_swift']??'') ?>"></div>
    </div>
  </div>

  <div class="settings-section">
    <div class="settings-section-title">Signature &amp; Stamp (Global — applied to all invoices)</div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Digital Signature Image <span style="font-weight:400;text-transform:none;font-size:11px;color:var(--text3)">(PNG with transparent background recommended)</span></label>
        <input type="file" name="signature_image" class="form-control" accept="image/*">
        <?php if (!empty($inv_settings['signature_image']) && file_exists($inv_settings['signature_image'])): ?>
        <img src="<?= h($inv_settings['signature_image']) ?>" class="img-preview" alt="Signature">
        <div style="font-size:11px;color:var(--green);margin-top:4px">✓ Signature uploaded</div>
        <?php else: ?>
        <div style="font-size:11px;color:var(--text3);margin-top:4px">No signature uploaded yet</div>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Company Stamp Image <span style="font-weight:400;text-transform:none;font-size:11px;color:var(--text3)">(PNG with transparent background recommended)</span></label>
        <input type="file" name="stamp_image" class="form-control" accept="image/*">
        <?php if (!empty($inv_settings['stamp_image']) && file_exists($inv_settings['stamp_image'])): ?>
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
    <div class="form-group"><label class="form-label">Footer Text (shown at bottom of every invoice)</label><textarea name="invoice_footer" class="form-control" style="min-height:60px" placeholder="Thank you for your business. Payment is due within 30 days."><?= h($inv_settings['invoice_footer']??'') ?></textarea></div>
  </div>

  <div style="display:flex;gap:10px">
    <button type="submit" class="btn btn-primary">Save Settings</button>
    <a href="invoices.php" class="btn btn-ghost">Cancel</a>
  </div>
</form>

<?php else: // ══ INVOICE LIST ══ ?>

<div class="inv-tabs">
  <a href="invoices.php" class="inv-tab <?= $tab==='list'?'active':'' ?>">📋 All Invoices</a>
  <?php if (isAdmin()): ?><a href="invoices.php?tab=settings" class="inv-tab <?= $tab==='settings'?'active':'' ?>">⚙ Invoice Settings</a><?php endif; ?>
</div>

<div class="inv-stats">
  <?php $sym_d = 'Rs. ';
  foreach ([
    ['💰','Total Invoiced', $sym_d.number_format($stats['total_invoiced']??0,2), 'rgba(249,115,22,.12)'],
    ['✅','Collected',      $sym_d.number_format($stats['total_collected']??0,2),'rgba(16,185,129,.12)'],
    ['⚠','Outstanding',    $sym_d.number_format(($stats['total_invoiced']??0)-($stats['total_collected']??0),2),'rgba(245,158,11,.12)'],
    ['🔴','Overdue',        $sym_d.number_format($stats['total_overdue']??0,2),  'rgba(239,68,68,.12)'],
    ['📝','Drafts',         (int)($stats['draft_count']??0).' invoices',          'rgba(99,102,241,.12)'],
  ] as [$ic,$lb,$vl,$bg]): ?>
  <div class="inv-stat">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px"><div style="width:32px;height:32px;border-radius:8px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;font-size:16px"><?= $ic ?></div></div>
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
      <?php foreach (['draft','sent','viewed','partial','paid','overdue','cancelled'] as $sv): ?>
      <option value="<?= $sv ?>" <?= $status_f===$sv?'selected':'' ?>><?= ucfirst($sv) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <?php if (isManager()): ?>
  <a href="invoices.php?new=1" class="btn btn-primary" style="text-decoration:none">＋ New Invoice</a>
  <?php endif; ?>
</div>

<?php if (empty($invoices)): ?>
<div class="card"><div class="empty-state"><div class="icon">🧾</div><p>No invoices yet.<?= isManager()?' <a href="invoices.php?new=1" style="color:var(--orange)">Create the first one</a>':'' ?></p></div></div>
<?php else: ?>
<?php foreach ($invoices as $inv):
  $sc = invColor($inv['status']); $balance = (float)$inv['balance_due'];
?>
<div class="inv-row" onclick="location.href='invoices.php?view=<?= $inv['id'] ?>'">
  <div class="inv-no"><?= h($inv['invoice_no']) ?></div>
  <div class="inv-client">
    <div class="inv-client-name"><?= $inv['client_name'] ? h($inv['client_name']) : h($inv['title']) ?></div>
    <div class="inv-client-sub"><?= $inv['project_title'] ? '📁 '.h($inv['project_title']).' · ' : '' ?><?= fDate($inv['issue_date']) ?><?= $inv['due_date'] ? ' · Due '.fDate($inv['due_date']) : '' ?></div>
  </div>
  <div><span class="inv-status" style="background:<?= $sc ?>18;color:<?= $sc ?>"><?= ucfirst($inv['status']) ?></span></div>
  <div class="inv-amount">
    <div class="inv-total-d"><?= invSym($inv['currency']) ?><?= number_format($inv['total'],2) ?></div>
    <?php if ($balance > 0 && $inv['status'] !== 'paid'): ?>
    <div class="inv-balance" style="color:<?= $inv['status']==='overdue'?'var(--red)':'var(--text3)' ?>">Due: <?= invSym($inv['currency']) ?><?= number_format($balance,2) ?></div>
    <?php elseif ($inv['status']==='paid'): ?>
    <div class="inv-balance" style="color:var(--green)">Paid ✓</div>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php endif; // end routing ?>

<script>
function calcRow(inp) {
    var row = inp.closest('.item-editor-row');
    var qty   = parseFloat(row.querySelector('[name="item_qty[]"]').value) || 0;
    var price = parseFloat(row.querySelector('[name="item_price[]"]').value) || 0;
    row.querySelector('[name="item_amount[]"]').value = (qty * price).toFixed(2);
    updateTotals();
}
function updateTotals() {
    var sub = 0;
    document.querySelectorAll('[name="item_amount[]"]').forEach(function(el){ sub += parseFloat(el.value) || 0; });
    var tax  = sub * ((parseFloat(document.getElementById('tax-rate')?.value)||0) / 100);
    var disc = parseFloat(document.getElementById('discount')?.value) || 0;
    var total = sub + tax - disc;
    var s = document.getElementById('preview-sub'); if(s) s.textContent = sub.toFixed(2);
    var t = document.getElementById('preview-tax'); if(t) t.textContent = tax.toFixed(2);
    var r = document.getElementById('preview-total'); if(r) r.textContent = total.toFixed(2);
}
function addRow() {
    var c = document.getElementById('items-container');
    var d = document.createElement('div'); d.className = 'item-editor-row';
    d.innerHTML = '<input type="text" name="item_desc[]" class="form-control" placeholder="Service or product description">'
        +'<input type="number" name="item_qty[]" class="form-control" placeholder="1" step="0.01" min="0.01" value="1" onchange="calcRow(this)" oninput="calcRow(this)">'
        +'<input type="number" name="item_price[]" class="form-control" placeholder="0.00" step="0.01" min="0" onchange="calcRow(this)" oninput="calcRow(this)">'
        +'<input type="text" name="item_amount[]" class="form-control" readonly value="" style="text-align:right;background:var(--bg4)">'
        +'<button type="button" class="btn btn-danger btn-sm btn-icon" onclick="removeRow(this)" style="padding:6px">✕</button>';
    c.appendChild(d);
}
function removeRow(btn) {
    if (document.querySelectorAll('.item-editor-row').length <= 1) return;
    btn.closest('.item-editor-row').remove(); updateTotals();
}
document.addEventListener('DOMContentLoaded', updateTotals);
</script>

<?php renderLayoutEnd(); ?>