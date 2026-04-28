<?php
/**
 * client_portal.php — Padak CRM Client Portal
 * Separate session namespace: portal_*
 * No dependency on CRM user session
 */
require_once 'config.php';
$db = getCRMDB();

// ── SESSION HELPERS ──
function portalSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('padak_portal');
        session_start();
    }
}
function portalClient(): ?array {
    if (empty($_SESSION['portal_id'])) return null;
    return [
        'id'         => (int)$_SESSION['portal_id'],
        'contact_id' => (int)$_SESSION['portal_contact_id'],
        'name'       => $_SESSION['portal_name'] ?? '',
        'email'      => $_SESSION['portal_email'] ?? '',
        'company'    => $_SESSION['portal_company'] ?? '',
    ];
}
function portalRequireLogin(): void {
    if (!portalClient()) {
        header('Location: client_portal.php'); exit;
    }
}

portalSession();

// ── UPLOAD DIR FOR PORTAL ──
define('PORTAL_UPLOAD_DIR', __DIR__ . '/uploads/portal/');
if (!is_dir(PORTAL_UPLOAD_DIR)) mkdir(PORTAL_UPLOAD_DIR, 0755, true);

// ── HELPERS ──
function ph(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function pDate(?string $d, string $fmt='M j, Y'): string {
    return $d ? date($fmt, strtotime($d)) : '—';
}
function invSym(string $c): string {
    return match($c) { 'USD'=>'$','EUR'=>'€','GBP'=>'£','INR'=>'₹', default=>'Rs. ' };
}

// ══════════════════════════════════════════
// POST HANDLERS (all unchanged)
// ══════════════════════════════════════════
ob_start();

// ── LOGIN ──
if ($_POST['action'] ?? '' === 'login') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $error = '';
    if ($email && $pass) {
        $stmt = $db->prepare("SELECT cp.*,c.name,c.company,c.id AS cid FROM client_portal cp JOIN contacts c ON c.id=cp.contact_id WHERE cp.email=? AND cp.status='active' LIMIT 1");
        $stmt->bind_param("s",$email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row && password_verify($pass, $row['password'])) {
            $_SESSION['portal_id']         = $row['id'];
            $_SESSION['portal_contact_id'] = $row['cid'];
            $_SESSION['portal_name']        = $row['name'];
            $_SESSION['portal_email']       = $row['email'];
            $_SESSION['portal_company']     = $row['company'];
            $db->query("UPDATE client_portal SET last_login=NOW() WHERE id={$row['id']}");
            ob_end_clean();
            header('Location: client_portal.php?page=dashboard'); exit;
        }
        $error = 'Invalid email or password.';
    } else {
        $error = 'Please enter email and password.';
    }
    ob_end_clean();
    $show_login_error = $error;
}

// ── LOGOUT ──
if (isset($_GET['logout'])) {
    session_destroy();
    ob_end_clean();
    header('Location: client_portal.php'); exit;
}

// ── SEND MESSAGE ──
if (($_POST['action'] ?? '') === 'send_message') {
    portalRequireLogin();
    $client = portalClient();
    $body    = trim($_POST['body'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $proj_id = (int)($_POST['project_id'] ?? 0) ?: null;
    if ($body) {
        $stmt = $db->prepare("INSERT INTO client_messages (contact_id,project_id,sender_type,sender_id,subject,body) VALUES (?,?,'client',?,?,?)");
        $stmt->bind_param("iiiss",$client['contact_id'],$proj_id,$client['contact_id'],$subject,$body);
        $stmt->execute();
    }
    ob_end_clean();
    header('Location: client_portal.php?page=messages'); exit;
}

// ── UPLOAD DOCUMENT ──
if (($_POST['action'] ?? '') === 'upload_doc') {
    portalRequireLogin();
    $client = portalClient();
    if (!empty($_FILES['file']['name']) && $_FILES['file']['error'] === 0) {
        $f    = $_FILES['file'];
        $ext  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx','xls','xlsx','ppt','pptx','jpg','jpeg','png','gif','txt','zip'];
        if (in_array($ext, $allowed) && $f['size'] <= 20*1024*1024) {
            $fname = 'portal_'.uniqid().'.'.$ext;
            if (move_uploaded_file($f['tmp_name'], PORTAL_UPLOAD_DIR.$fname)) {
                $title  = trim($_POST['title'] ?? '') ?: basename($f['name']);
                $pid    = (int)($_POST['project_id'] ?? 0) ?: null;
                $uid    = $client['contact_id'];
                $oname  = basename($f['name']);
                $fsize  = (int)$f['size'];
                $ftype  = $f['type'];
                $admin  = $db->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetch_assoc();
                $admin_id = $admin ? (int)$admin['id'] : 1;
                $cat    = 'Client Upload';
                $access = 'manager';
                $stmt2  = $db->prepare("INSERT INTO documents (title,filename,original_name,file_size,file_type,contact_id,category,access,uploaded_by) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt2->bind_param("sssisiisi",$title,$fname,$oname,$fsize,$ftype,$uid,$cat,$access,$admin_id);
                $stmt2->execute();
            }
        }
    }
    ob_end_clean();
    header('Location: client_portal.php?page=documents'); exit;
}

ob_end_clean();

// ══════════════════════════════════════════
// ROUTING (all unchanged)
// ══════════════════════════════════════════
$client = portalClient();
$page   = $_GET['page'] ?? ($client ? 'dashboard' : 'login');
if (!$client && $page !== 'login') $page = 'login';

$projects  = [];
$invoices  = [];
$messages  = [];
$docs      = [];
$proj_det  = null;

if ($client) {
    $cid = $client['contact_id'];

    $projects = $db->query("
        SELECT p.*, u.name AS pm_name,
            (SELECT COUNT(*) FROM tasks t WHERE t.project_id=p.id AND t.status='done') AS done_tasks,
            (SELECT COUNT(*) FROM tasks t WHERE t.project_id=p.id) AS total_tasks
        FROM projects p
        LEFT JOIN users u ON u.id=p.created_by
        WHERE p.contact_id=$cid
        ORDER BY p.created_at DESC
    ")->fetch_all(MYSQLI_ASSOC);

    $invoices = $db->query("
        SELECT i.*, COALESCE(i.total-i.amount_paid,0) AS balance_due
        FROM invoices i
        WHERE i.contact_id=$cid
        ORDER BY i.issue_date DESC
    ")->fetch_all(MYSQLI_ASSOC);

    $messages = $db->query("
        SELECT cm.*, p.title AS project_title,
            CASE WHEN cm.sender_type='staff' THEN u.name ELSE NULL END AS staff_name
        FROM client_messages cm
        LEFT JOIN projects p ON p.id=cm.project_id
        LEFT JOIN users u ON u.id=cm.sender_id AND cm.sender_type='staff'
        WHERE cm.contact_id=$cid
        ORDER BY cm.created_at DESC
        LIMIT 50
    ")->fetch_all(MYSQLI_ASSOC);

    $docs = $db->query("
        SELECT d.*, u.name AS uploader
        FROM documents d
        LEFT JOIN users u ON u.id=d.uploaded_by
        WHERE d.contact_id=$cid OR d.project_id IN (SELECT id FROM projects WHERE contact_id=$cid)
        ORDER BY d.created_at DESC
    ")->fetch_all(MYSQLI_ASSOC);

    if ($page === 'messages') {
        $db->query("UPDATE client_messages SET is_read=1 WHERE contact_id=$cid AND sender_type='staff' AND is_read=0");
    }

    if ($page === 'project' && isset($_GET['id'])) {
        $pid = (int)$_GET['id'];
        $r = $db->query("SELECT * FROM projects WHERE id=$pid AND contact_id=$cid")->fetch_assoc();
        if ($r) {
            $proj_det = $r;
            $proj_det['tasks'] = $db->query("SELECT t.*,u.name AS assignee FROM tasks t LEFT JOIN users u ON u.id=t.assigned_to WHERE t.project_id=$pid ORDER BY FIELD(t.status,'todo','in_progress','review','done'), t.due_date")->fetch_all(MYSQLI_ASSOC);
        }
    }

    $unread = (int)$db->query("SELECT COUNT(*) AS c FROM client_messages WHERE contact_id=$cid AND sender_type='staff' AND is_read=0")->fetch_assoc()['c'];
}

// ── FILE SERVE (unchanged) ──
if (isset($_GET['view_doc']) && $client) {
    $did = (int)$_GET['view_doc'];
    $doc = $db->query("SELECT * FROM documents WHERE id=$did AND (contact_id=$cid OR project_id IN (SELECT id FROM projects WHERE contact_id=$cid))")->fetch_assoc();
    if ($doc) {
        $path = UPLOAD_DOC_DIR . $doc['filename'];
        if (file_exists($path) && strtolower(pathinfo($doc['original_name'],PATHINFO_EXTENSION)) === 'pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="'.addslashes($doc['original_name']).'"');
            readfile($path); exit;
        }
    }
    die('Not found.');
}
if (isset($_GET['dl_doc']) && $client) {
    $did = (int)$_GET['dl_doc'];
    $doc = $db->query("SELECT * FROM documents WHERE id=$did AND (contact_id=$cid OR project_id IN (SELECT id FROM projects WHERE contact_id=$cid))")->fetch_assoc();
    if ($doc) {
        $try = [PORTAL_UPLOAD_DIR, UPLOAD_DOC_DIR];
        foreach ($try as $dir) {
            if (file_exists($dir.$doc['filename'])) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.addslashes($doc['original_name']).'"');
                header('Content-Length: '.filesize($dir.$doc['filename']));
                readfile($dir.$doc['filename']); exit;
            }
        }
    }
    die('File not found.');
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Client Portal — Padak</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

/* ── DARK (default) ── */
:root,[data-theme="dark"]{
  --bg:#0d1117;--bg2:#161b27;--bg3:#1e2538;--bg4:#252d40;
  --border:#2a3348;--border2:#3a4560;
  --text:#e8eaf0;--text2:#9aa3b8;--text3:#5a6478;
  --orange:#f97316;--orange-bg:rgba(249,115,22,.1);--orange-border:rgba(249,115,22,.25);
  --green:#10b981;--green-bg:rgba(16,185,129,.1);
  --red:#ef4444;--red-bg:rgba(239,68,68,.1);
  --blue:#6366f1;--blue-bg:rgba(99,102,241,.1);
  --yellow:#f59e0b;--yellow-bg:rgba(245,158,11,.1);
  --radius:12px;--radius-sm:8px;--radius-lg:18px;
  --font:'Plus Jakarta Sans',sans-serif;--font-d:'Bricolage Grotesque',sans-serif;
  --sidebar:260px;
  --shadow:0 1px 4px rgba(0,0,0,.4);
  --shadow-lg:0 8px 32px rgba(0,0,0,.5);
  --card-hover:rgba(255,255,255,.015);
}

/* ── LIGHT ── */
[data-theme="light"]{
  --bg:#f4f6fb;--bg2:#ffffff;--bg3:#f0f2f7;--bg4:#e8eaf2;
  --border:#e2e5ef;--border2:#c8cce0;
  --text:#111827;--text2:#4b5563;--text3:#9ca3af;
  --orange:#ea6c0a;--orange-bg:rgba(234,108,10,.08);--orange-border:rgba(234,108,10,.2);
  --green:#059669;--green-bg:rgba(5,150,105,.08);
  --red:#dc2626;--red-bg:rgba(220,38,38,.08);
  --blue:#4f46e5;--blue-bg:rgba(79,70,229,.08);
  --yellow:#d97706;--yellow-bg:rgba(217,119,6,.08);
  --shadow:0 1px 4px rgba(0,0,0,.06);
  --shadow-lg:0 8px 32px rgba(0,0,0,.1);
  --card-hover:rgba(0,0,0,.01);
}

/* ── BASE ── */
body{font-family:var(--font);background:var(--bg);color:var(--text);min-height:100vh;transition:background .2s,color .2s}
a{color:inherit;text-decoration:none}
button,input,select,textarea{font-family:var(--font)}

/* ── LOGIN ── */
.portal-login{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;background:var(--bg);position:relative;overflow:hidden}
.portal-login::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 50% -10%,rgba(249,115,22,.12),transparent);pointer-events:none}
.login-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:44px 40px;width:100%;max-width:420px;position:relative;z-index:1;box-shadow:var(--shadow-lg)}
.login-brand{text-align:center;margin-bottom:36px}
.login-logo{width:44px;height:44px;background:var(--orange);border-radius:12px;display:inline-flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:12px}
.login-brand-name{font-family:var(--font-d);font-size:24px;font-weight:800;color:var(--text);letter-spacing:-.3px}
.login-brand-name span{color:var(--orange)}
.login-brand-sub{font-size:13px;color:var(--text3);margin-top:4px}
.login-theme-btn{position:absolute;top:16px;right:16px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;color:var(--text2);transition:all .15s}
.login-theme-btn:hover{background:var(--bg4);color:var(--text)}
.lf{margin-bottom:18px}
.lf label{display:block;font-size:12px;font-weight:700;color:var(--text2);margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em}
.lf input{width:100%;padding:11px 14px;background:var(--bg3);border:1.5px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:14px;transition:border-color .15s,box-shadow .15s;outline:none}
.lf input:focus{border-color:var(--orange);box-shadow:0 0 0 3px var(--orange-bg)}
.lf input::placeholder{color:var(--text3)}
.btn-login{width:100%;padding:13px;background:var(--orange);color:#fff;border:none;border-radius:var(--radius-sm);font-size:14px;font-weight:700;cursor:pointer;margin-top:4px;transition:opacity .15s,transform .1s;letter-spacing:.02em}
.btn-login:hover{opacity:.92}
.btn-login:active{transform:scale(.99)}
.login-error{background:var(--red-bg);border:1px solid rgba(239,68,68,.25);color:var(--red);padding:10px 14px;border-radius:var(--radius-sm);font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px}

/* ── PORTAL LAYOUT ── */
.portal-wrap{display:flex;min-height:100vh}

/* ── SIDEBAR ── */
.portal-sidebar{width:var(--sidebar);min-width:var(--sidebar);background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:50;transition:transform .25s}
.portal-brand{padding:22px 20px 18px;border-bottom:1px solid var(--border);flex-shrink:0;display:flex;align-items:center;gap:12px}
.portal-brand-icon{width:34px;height:34px;background:var(--orange);border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0}
.portal-brand-text{}
.portal-brand-name{font-family:var(--font-d);font-size:16px;font-weight:800;line-height:1.1;letter-spacing:-.2px}
.portal-brand-name span{color:var(--orange)}
.portal-brand-sub{font-size:10.5px;color:var(--text3);margin-top:1px}

/* Navigation */
.portal-nav{flex:1;padding:12px 10px;overflow-y:auto}
.pnav-section{font-size:10px;font-weight:800;color:var(--text3);text-transform:uppercase;letter-spacing:.08em;padding:8px 10px 4px;margin-top:6px}
.pnav-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:var(--radius-sm);cursor:pointer;color:var(--text2);font-size:13px;font-weight:500;transition:background .12s,color .12s;white-space:nowrap;position:relative}
.pnav-item:hover{background:var(--bg3);color:var(--text)}
.pnav-item.active{background:var(--orange-bg);color:var(--orange);font-weight:700}
.pnav-item.active::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:3px;background:var(--orange);border-radius:0 3px 3px 0}
.pnav-icon{font-size:16px;flex-shrink:0;width:20px;text-align:center}
.pnav-badge{margin-left:auto;background:var(--red);color:#fff;font-size:9px;font-weight:800;padding:1px 6px;border-radius:99px;min-width:18px;text-align:center}

/* User + Controls */
.portal-user{padding:14px 16px;border-top:1px solid var(--border);flex-shrink:0}
.portal-user-row{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.portal-user-avatar{width:34px;height:34px;border-radius:50%;background:var(--orange-bg);border:2px solid var(--orange-border);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:var(--orange);flex-shrink:0}
.portal-user-name{font-size:13px;font-weight:700;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.portal-user-co{font-size:11px;color:var(--text3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.portal-user-actions{display:flex;gap:6px;align-items:center}
.portal-signout{font-size:12px;color:var(--text3);transition:color .15s;flex:1}
.portal-signout:hover{color:var(--red)}
.portal-theme-btn{width:30px;height:30px;background:var(--bg3);border:1px solid var(--border);border-radius:7px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:14px;color:var(--text2);transition:all .15s;flex-shrink:0}
.portal-theme-btn:hover{background:var(--bg4);color:var(--orange)}

/* ── MAIN ── */
.portal-main{flex:1;margin-left:var(--sidebar);min-height:100vh;display:flex;flex-direction:column}
.portal-topbar{height:58px;border-bottom:1px solid var(--border);background:var(--bg2);display:flex;align-items:center;padding:0 32px;gap:12px;flex-shrink:0;position:sticky;top:0;z-index:40;backdrop-filter:blur(8px)}
.portal-breadcrumb{font-size:12.5px;color:var(--text3);display:flex;align-items:center;gap:6px;flex:1}
.portal-breadcrumb span{color:var(--text2);font-weight:600}
.portal-content{padding:28px 32px;flex:1}

/* ── PAGE HEADER ── */
.page-hd{margin-bottom:26px}
.page-hd-title{font-family:var(--font-d);font-size:24px;font-weight:800;color:var(--text);letter-spacing:-.3px;margin-bottom:4px}
.page-hd-sub{font-size:13px;color:var(--text3)}

/* ── STAT CARDS ── */
.pstats{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:14px;margin-bottom:24px}
.pstat{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;position:relative;overflow:hidden;transition:border-color .15s,box-shadow .15s}
.pstat:hover{border-color:var(--border2);box-shadow:var(--shadow)}
.pstat-icon{position:absolute;top:14px;right:16px;font-size:22px;opacity:.35}
.pstat-val{font-size:26px;font-weight:800;font-family:var(--font-d);color:var(--text);margin-bottom:3px;letter-spacing:-.5px}
.pstat-lbl{font-size:11px;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;font-weight:600}
.pstat-trend{font-size:11px;margin-top:6px;font-weight:600}

/* ── CARD ── */
.pcard{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:22px;margin-bottom:16px}
.pcard-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;gap:10px}
.pcard-title{font-size:11px;font-weight:800;color:var(--text3);text-transform:uppercase;letter-spacing:.07em}
.pcard-action{font-size:12px;color:var(--orange);font-weight:600;transition:opacity .15s}
.pcard-action:hover{opacity:.8}

/* ── PROJECT CARD ── */
.proj-card{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;margin-bottom:10px;cursor:pointer;transition:border-color .15s,box-shadow .15s,background .15s}
.proj-card:hover{border-color:var(--orange-border);box-shadow:0 2px 12px rgba(249,115,22,.1);background:var(--card-hover)}
.proj-card-title{font-size:15px;font-weight:700;color:var(--text);margin-bottom:4px;line-height:1.3}
.proj-card-meta{font-size:12px;color:var(--text3);margin-bottom:12px}
.proj-card-footer{display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--text3);margin-top:8px}
.proj-progress-wrap{height:6px;background:var(--bg4);border-radius:99px;overflow:hidden;margin:10px 0 4px}
.proj-progress-fill{height:100%;background:linear-gradient(90deg,var(--orange),#f59e0b);border-radius:99px;transition:width .5s ease}

/* ── STATUS BADGE ── */
.sbadge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}

/* ── INVOICE ROW ── */
.inv-row{display:flex;align-items:center;gap:14px;padding:14px 18px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:8px;transition:border-color .15s}
.inv-row:hover{border-color:var(--border2)}
.inv-num{font-size:13px;font-weight:800;color:var(--text);font-family:var(--font-d)}
.inv-title{font-size:12px;color:var(--text2);margin-top:1px}
.inv-dates{font-size:11.5px;color:var(--text3)}
.inv-amount{font-size:16px;font-weight:800;color:var(--text);font-family:var(--font-d);text-align:right;min-width:100px}

/* ── TASK ROW ── */
.task-row{display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--bg4);border-radius:var(--radius-sm);margin-bottom:6px;transition:background .12s}
.task-row:hover{background:var(--bg3)}
.task-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}

/* ── DOCUMENT ROW ── */
.doc-row{display:flex;align-items:center;gap:14px;padding:14px 16px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:8px;transition:border-color .15s}
.doc-row:hover{border-color:var(--border2)}
.doc-icon{width:40px;height:40px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.doc-name{font-size:13.5px;font-weight:700;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.doc-meta{font-size:11.5px;color:var(--text3);margin-top:2px}
.doc-btn{width:34px;height:34px;background:var(--bg4);border:1px solid var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;color:var(--text2);transition:all .15s;flex-shrink:0;cursor:pointer}
.doc-btn:hover{background:var(--orange-bg);border-color:var(--orange-border);color:var(--orange)}

/* ── MESSAGES ── */
.msg-wrap{display:flex;margin-bottom:14px}
.msg-wrap.mine{justify-content:flex-end}
.msg-inner{}
.msg-sender{font-size:11px;color:var(--text3);margin-bottom:3px;padding:0 4px}
.msg-wrap.mine .msg-sender{text-align:right}
.msg-bubble{max-width:72%;padding:12px 16px;border-radius:14px;font-size:13.5px;line-height:1.6;word-break:break-word}
.msg-mine{background:var(--orange);color:#fff;border-bottom-right-radius:4px}
.msg-staff{background:var(--bg3);border:1px solid var(--border);color:var(--text);border-bottom-left-radius:4px}
.msg-time{font-size:10.5px;color:var(--text3);margin-top:4px;padding:0 4px}
.msg-wrap.mine .msg-time{text-align:right}

/* ── COMPOSE BOX ── */
.compose-box{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:20px}

/* ── UPLOAD AREA ── */
.upload-panel{background:var(--bg3);border:2px dashed var(--border);border-radius:var(--radius);padding:24px;margin-bottom:16px;transition:border-color .2s}
.upload-panel:hover,.upload-panel.drag-over{border-color:var(--orange)}
.upload-panel.drag-over{background:var(--orange-bg)}

/* ── EMPTY STATE ── */
.pempty{text-align:center;padding:52px 20px;color:var(--text3)}
.pempty-icon{font-size:42px;margin-bottom:12px;opacity:.6}
.pempty-title{font-size:15px;font-weight:700;color:var(--text2);margin-bottom:6px}
.pempty-desc{font-size:13px;color:var(--text3);line-height:1.6}

/* ── BUTTONS ── */
.pbtn{padding:9px 18px;border-radius:var(--radius-sm);font-size:13px;font-weight:700;border:none;cursor:pointer;transition:opacity .15s,transform .1s;display:inline-flex;align-items:center;gap:7px;white-space:nowrap}
.pbtn:active{transform:scale(.98)}
.pbtn-primary{background:var(--orange);color:#fff}
.pbtn-primary:hover{opacity:.9}
.pbtn-ghost{background:var(--bg4);color:var(--text2);border:1px solid var(--border)}
.pbtn-ghost:hover{background:var(--bg3);color:var(--text)}
.pbtn-sm{padding:7px 13px;font-size:12px}

/* ── FORM CONTROLS ── */
.pform-group{margin-bottom:16px}
.pform-label{display:block;font-size:11.5px;font-weight:700;color:var(--text2);margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em}
.pform-control{width:100%;padding:10px 13px;background:var(--bg4);border:1.5px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:13.5px;outline:none;transition:border-color .15s,box-shadow .15s}
.pform-control:focus{border-color:var(--orange);box-shadow:0 0 0 3px var(--orange-bg)}
.pform-control::placeholder{color:var(--text3)}

/* ── ALERT BANNER ── */
.palert{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-radius:var(--radius);margin-bottom:16px;gap:12px;flex-wrap:wrap}
.palert-orange{background:var(--orange-bg);border:1px solid var(--orange-border);color:var(--orange)}

/* ── DETAIL GRID ── */
.detail-row{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--border);font-size:13px}
.detail-row:last-child{border-bottom:none}
.detail-lbl{color:var(--text3);font-weight:500}
.detail-val{color:var(--text);font-weight:600}

/* ── MOBILE TOGGLE ── */
.mob-toggle{display:none;position:fixed;top:12px;left:12px;z-index:200;background:var(--orange);color:#fff;border:none;border-radius:9px;padding:9px 11px;font-size:16px;cursor:pointer;box-shadow:0 2px 8px rgba(249,115,22,.4)}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:45}

/* ── RESPONSIVE ── */
@media(max-width:768px){
  .mob-toggle{display:flex}
  .portal-sidebar{transform:translateX(-100%)}
  .portal-sidebar.open{transform:none}
  .sidebar-overlay.open{display:block}
  .portal-main{margin-left:0}
  .portal-topbar{padding:0 16px}
  .portal-content{padding:16px}
  .pstats{grid-template-columns:repeat(2,1fr)}
  .proj-two-col{grid-template-columns:1fr!important}
  .msg-bubble{max-width:88%}
  .inv-row{flex-wrap:wrap}
  .upload-grid{grid-template-columns:1fr!important}
}
@media(max-width:400px){
  .pstats{grid-template-columns:1fr}
  .login-card{padding:28px 24px}
}
</style>
</head>
<body>
<?php // ── Inline script: theme from localStorage, no flash ── ?>
<script>
(function(){
    var t=localStorage.getItem('padak_portal_theme')||'dark';
    document.documentElement.setAttribute('data-theme',t);
})();
</script>

<?php if ($page === 'login'): ?>
<!-- ══════════ LOGIN ══════════ -->
<div class="portal-login">
  <div class="login-card">
    <button class="login-theme-btn" onclick="portalToggleTheme()" title="Toggle theme" id="login-theme-btn">🌙</button>
    <div class="login-brand">
      <div class="login-logo">🧩</div>
      <div class="login-brand-name">Padak <span>CRM</span></div>
      <div class="login-brand-sub">Client Portal — Secure Access</div>
    </div>
    <?php if (!empty($show_login_error)): ?>
    <div class="login-error">⚠ <?= ph($show_login_error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="action" value="login">
      <div class="lf">
        <label>Email Address</label>
        <input type="email" name="email" required autofocus placeholder="you@example.com">
      </div>
      <div class="lf">
        <label>Password</label>
        <input type="password" name="password" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn-login">Sign In →</button>
    </form>
    <div style="text-align:center;margin-top:22px;font-size:12.5px;color:var(--text3)">
      Don't have access? <a href="mailto:careers@thepadak.com" style="color:var(--orange);font-weight:600">Contact Padak team</a>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ══════════ AUTHENTICATED PORTAL ══════════ -->
<button class="mob-toggle" onclick="portalToggleSidebar()">☰</button>
<div class="sidebar-overlay" id="sidebar-overlay" onclick="portalToggleSidebar()"></div>

<div class="portal-wrap">
  <!-- SIDEBAR -->
  <aside class="portal-sidebar" id="portal-sidebar">
    <div class="portal-brand">
      <div class="portal-brand-icon">🧩</div>
      <div class="portal-brand-text">
        <div class="portal-brand-name">Padak <span>CRM</span></div>
        <div class="portal-brand-sub">Client Portal</div>
      </div>
    </div>

    <nav class="portal-nav">
      <div class="pnav-section">Overview</div>
      <?php
      $navItems = [
        ['dashboard', '📊', 'Dashboard'],
        ['projects',  '📁', 'My Projects'],
      ];
      foreach ($navItems as $ni):
        $act = $page === $ni[0] ? ' active' : '';
      ?>
      <a href="client_portal.php?page=<?= $ni[0] ?>" class="pnav-item<?= $act ?>" onclick="if(window.innerWidth<769)portalToggleSidebar()">
        <span class="pnav-icon"><?= $ni[1] ?></span><?= $ni[2] ?>
      </a>
      <?php endforeach; ?>

      <div class="pnav-section" style="margin-top:8px">Billing</div>
      <a href="client_portal.php?page=invoices" class="pnav-item<?= $page==='invoices'?' active':'' ?>" onclick="if(window.innerWidth<769)portalToggleSidebar()">
        <span class="pnav-icon">🧾</span>Invoices
      </a>

      <div class="pnav-section" style="margin-top:8px">Files & Communication</div>
      <a href="client_portal.php?page=documents" class="pnav-item<?= $page==='documents'?' active':'' ?>" onclick="if(window.innerWidth<769)portalToggleSidebar()">
        <span class="pnav-icon">📄</span>Documents
      </a>
      <a href="client_portal.php?page=messages" class="pnav-item<?= $page==='messages'?' active':'' ?>" onclick="if(window.innerWidth<769)portalToggleSidebar()">
        <span class="pnav-icon">💬</span>Messages
        <?php if (!empty($unread)): ?><span class="pnav-badge"><?= $unread ?></span><?php endif; ?>
      </a>
    </nav>

    <div class="portal-user">
      <div class="portal-user-row">
        <div class="portal-user-avatar"><?= strtoupper(substr($client['name'],0,1)) ?></div>
        <div style="flex:1;min-width:0">
          <div class="portal-user-name"><?= ph($client['name']) ?></div>
          <div class="portal-user-co"><?= ph($client['company'] ?: $client['email']) ?></div>
        </div>
      </div>
      <div class="portal-user-actions">
        <a href="client_portal.php?logout=1" class="portal-signout">↩ Sign out</a>
      </div>
    </div>
  </aside>

  <!-- MAIN AREA -->
  <main class="portal-main">
    <!-- Top bar -->
    <div class="portal-topbar">
      <div class="portal-breadcrumb">
        <?php
        $titles = ['dashboard'=>'Dashboard','projects'=>'My Projects','project'=>'Project Detail',
                   'invoices'=>'Invoices','documents'=>'Documents','messages'=>'Messages'];
        echo '<a href="client_portal.php?page=dashboard" style="color:var(--text3)">Portal</a>';
        echo ' <span style="color:var(--border2)">/</span> ';
        echo '<span>'.($titles[$page] ?? ucfirst($page)).'</span>';
        if ($page==='project' && $proj_det) echo ' <span style="color:var(--border2)">/</span> <span>'.ph($proj_det['title']).'</span>';
        ?>
      </div>
      <div style="display:flex;align-items:center;gap:10px;font-size:12px;color:var(--text3)">
        <span><?= date('D, M j, Y') ?></span>
      </div>
      <button class="portal-theme-btn" onclick="portalToggleTheme()" id="sidebar-theme-btn" title="Toggle dark/light">🌙</button>
    </div>

    <div class="portal-content">
    <?php

    // ── Helper functions (inside authenticated block) ──
    function projColor(string $s): array {
        return match($s) {
            'active'    => ['#10b981','rgba(16,185,129,.12)'],
            'completed' => ['#6366f1','rgba(99,102,241,.12)'],
            'on_hold'   => ['#f59e0b','rgba(245,158,11,.12)'],
            'planning'  => ['#94a3b8','rgba(148,163,184,.12)'],
            default     => ['#94a3b8','rgba(148,163,184,.12)'],
        };
    }
    function taskDot(string $s): string {
        return match($s) {
            'done'        => 'var(--green)',
            'in_progress' => 'var(--orange)',
            'review'      => 'var(--blue)',
            default       => 'var(--text3)',
        };
    }
    function invColor(string $s): string {
        return match($s) {
            'paid'=>'#10b981','partial'=>'#f59e0b','overdue'=>'#ef4444',
            'sent'=>'#6366f1','viewed'=>'#8b5cf6','draft'=>'#94a3b8',
            default=>'#94a3b8'
        };
    }
    function docIcon(string $name): array {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return match(true) {
            $ext === 'pdf'                                     => ['📕','rgba(239,68,68,.15)','#ef4444'],
            in_array($ext, ['doc','docx'])                    => ['📘','rgba(99,102,241,.15)','#6366f1'],
            in_array($ext, ['xls','xlsx','csv'])              => ['📗','rgba(16,185,129,.15)','#10b981'],
            in_array($ext, ['jpg','jpeg','png','gif','webp']) => ['🖼','rgba(245,158,11,.15)','#f59e0b'],
            in_array($ext, ['zip','rar','7z'])                => ['📦','rgba(148,163,184,.15)','#94a3b8'],
            default                                            => ['📄','rgba(99,102,241,.12)','#6366f1'],
        };
    }

    // ══════════ DASHBOARD ══════════
    if ($page === 'dashboard'):
        $active_proj = array_filter($projects, fn($p) => $p['status'] === 'active');
        $open_inv    = array_filter($invoices, fn($i) => !in_array($i['status'], ['paid','cancelled']));
        $total_due   = array_sum(array_column(
            array_filter($invoices, fn($i) => $i['status'] !== 'paid' && $i['status'] !== 'cancelled'),
            'balance_due'
        ));
    ?>
    <div class="page-hd">
      <div class="page-hd-title">Welcome back, <?= ph(explode(' ', $client['name'])[0]) ?> 👋</div>
      <div class="page-hd-sub"><?= ph($client['company'] ?: '') ?><?= $client['company'] ? ' · ' : '' ?><?= date('l, F j, Y') ?></div>
    </div>

    <div class="pstats">
      <div class="pstat"><span class="pstat-icon">📁</span><div class="pstat-val"><?= count($projects) ?></div><div class="pstat-lbl">Total Projects</div></div>
      <div class="pstat"><span class="pstat-icon">⚡</span><div class="pstat-val"><?= count($active_proj) ?></div><div class="pstat-lbl">Active Now</div></div>
      <div class="pstat"><span class="pstat-icon">🧾</span><div class="pstat-val"><?= count($open_inv) ?></div><div class="pstat-lbl">Open Invoices</div></div>
      <div class="pstat"><span class="pstat-icon">💰</span><div class="pstat-val" style="color:<?= $total_due > 0 ? 'var(--red)' : 'var(--green)' ?>;font-size:<?= strlen('Rs.'.number_format($total_due,0))>10?'18px':'26px' ?>">Rs.&nbsp;<?= number_format($total_due, 0) ?></div><div class="pstat-lbl">Balance Due</div></div>
    </div>

    <?php if ($projects): ?>
    <div class="pcard">
      <div class="pcard-hd">
        <div class="pcard-title">Recent Projects</div>
        <?php if (count($projects) > 3): ?>
        <a href="client_portal.php?page=projects" class="pcard-action">View all <?= count($projects) ?> →</a>
        <?php endif; ?>
      </div>
      <?php foreach (array_slice($projects, 0, 3) as $p):
        [$pc, $pbg] = projColor($p['status']);
        $pct = (int)$p['progress'];
      ?>
      <div class="proj-card" onclick="location.href='client_portal.php?page=project&id=<?= $p['id'] ?>'">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px">
          <div class="proj-card-title"><?= ph($p['title']) ?></div>
          <span class="sbadge" style="background:<?= $pbg ?>;color:<?= $pc ?>;margin-left:10px;flex-shrink:0"><?= ucfirst(str_replace('_',' ',$p['status'])) ?></span>
        </div>
        <div class="proj-card-meta">Managed by <?= ph($p['pm_name'] ?? 'Padak') ?><?= $p['due_date'] ? ' · Due '.pDate($p['due_date']) : '' ?></div>
        <div class="proj-progress-wrap"><div class="proj-progress-fill" style="width:<?= $pct ?>%"></div></div>
        <div class="proj-card-footer">
          <span><?= $pct ?>% complete · <?= $p['done_tasks'] ?>/<?= $p['total_tasks'] ?> tasks</span>
          <span><?= $p['due_date'] ? 'Due '.pDate($p['due_date'],'M j, Y') : 'No deadline' ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="pcard"><div class="pempty"><div class="pempty-icon">📁</div><div class="pempty-title">No projects yet</div><div class="pempty-desc">Your projects will appear here once the team sets them up.</div></div></div>
    <?php endif; ?>

    <?php if ($invoices): ?>
    <div class="pcard">
      <div class="pcard-hd">
        <div class="pcard-title">Recent Invoices</div>
        <a href="client_portal.php?page=invoices" class="pcard-action">View all →</a>
      </div>
      <?php foreach (array_slice($invoices, 0, 3) as $inv):
        $ic = invColor($inv['status']);
        if ($inv['status'] === 'draft') continue;
      ?>
      <div class="inv-row">
        <div style="flex:1;min-width:0">
          <div class="inv-num"><?= ph($inv['invoice_no']) ?></div>
          <div class="inv-title"><?= ph($inv['title']) ?></div>
          <div class="inv-dates">Due <?= pDate($inv['due_date']) ?></div>
        </div>
        <span class="sbadge" style="background:<?= $ic ?>18;color:<?= $ic ?>"><?= ucfirst($inv['status']) ?></span>
        <div class="inv-amount">
          <?= invSym($inv['currency']) ?><?= number_format($inv['total'], 2) ?>
          <?php if ($inv['balance_due'] > 0): ?><div style="font-size:11px;color:var(--red);font-weight:700">Due: <?= invSym($inv['currency']) ?><?= number_format($inv['balance_due'], 2) ?></div><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($unread)): ?>
    <div class="palert palert-orange">
      <div style="display:flex;align-items:center;gap:10px"><span style="font-size:20px">💬</span><div><div style="font-weight:700"><?= $unread ?> new message<?= $unread > 1 ? 's' : '' ?> from Padak team</div><div style="font-size:12px;opacity:.8">Your project manager has replied</div></div></div>
      <a href="client_portal.php?page=messages" class="pbtn pbtn-primary pbtn-sm">View →</a>
    </div>
    <?php endif; ?>


    <?php // ══════════ PROJECTS LIST ══════════
    elseif ($page === 'projects'): ?>
    <div class="page-hd">
      <div class="page-hd-title">My Projects</div>
      <div class="page-hd-sub"><?= count($projects) ?> project<?= count($projects) != 1 ? 's' : '' ?> linked to your account</div>
    </div>
    <?php if (!$projects): ?>
    <div class="pcard"><div class="pempty"><div class="pempty-icon">📁</div><div class="pempty-title">No projects yet</div><div class="pempty-desc">Your projects will appear here once the Padak team links them to your account.</div></div></div>
    <?php else: ?>
    <?php foreach ($projects as $p):
      [$pc, $pbg] = projColor($p['status']); $pct = (int)$p['progress'];
    ?>
    <div class="proj-card" onclick="location.href='client_portal.php?page=project&id=<?= $p['id'] ?>'">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:4px">
        <div>
          <div class="proj-card-title"><?= ph($p['title']) ?></div>
          <div class="proj-card-meta">Managed by <?= ph($p['pm_name'] ?? 'Padak') ?><?= $p['due_date'] ? ' · Due '.pDate($p['due_date']) : '' ?></div>
        </div>
        <span class="sbadge" style="background:<?= $pbg ?>;color:<?= $pc ?>;flex-shrink:0;margin-left:12px"><?= ucfirst(str_replace('_',' ',$p['status'])) ?></span>
      </div>
      <div class="proj-progress-wrap"><div class="proj-progress-fill" style="width:<?= $pct ?>%"></div></div>
      <div class="proj-card-footer">
        <span><?= $pct ?>% complete</span>
        <span><?= $p['done_tasks'] ?>/<?= $p['total_tasks'] ?> tasks done</span>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>


    <?php // ══════════ PROJECT DETAIL ══════════
    elseif ($page === 'project' && $proj_det):
      [$pc, $pbg] = projColor($proj_det['status']); $pct = (int)$proj_det['progress'];
      $done_t = array_reduce($proj_det['tasks'] ?? [], fn($c, $t) => $c + ($t['status'] === 'done' ? 1 : 0), 0);
    ?>
    <div style="margin-bottom:18px"><a href="client_portal.php?page=projects" style="color:var(--text3);font-size:13px;transition:color .15s" onmouseover="this.style.color='var(--orange)'" onmouseout="this.style.color='var(--text3)'">← All Projects</a></div>
    <div class="page-hd">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px">
        <div>
          <div class="page-hd-title"><?= ph($proj_det['title']) ?></div>
          <?php if ($proj_det['description']): ?><div class="page-hd-sub"><?= ph($proj_det['description']) ?></div><?php endif; ?>
        </div>
        <span class="sbadge" style="background:<?= $pbg ?>;color:<?= $pc ?>;font-size:12px;padding:5px 14px"><?= ucfirst(str_replace('_',' ',$proj_det['status'])) ?></span>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;align-items:start" class="proj-two-col">
      <div>
        <div class="pcard">
          <div class="pcard-hd"><div class="pcard-title">Progress — <?= $pct ?>%</div><span style="font-size:12px;color:var(--text3)"><?= $done_t ?>/<?= count($proj_det['tasks'] ?? []) ?> tasks</span></div>
          <div class="proj-progress-wrap" style="height:8px"><div class="proj-progress-fill" style="width:<?= $pct ?>%"></div></div>
        </div>

        <?php if ($proj_det['tasks']): ?>
        <div class="pcard">
          <div class="pcard-hd"><div class="pcard-title">Task Overview</div></div>
          <?php foreach ($proj_det['tasks'] as $t): ?>
          <div class="task-row">
            <div class="task-dot" style="background:<?= taskDot($t['status']) ?>"></div>
            <div style="flex:1;min-width:0">
              <div style="font-size:13px;font-weight:600;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap<?= $t['status']==='done'?';text-decoration:line-through;opacity:.5':'' ?>"><?= ph($t['title']) ?></div>
              <?php if ($t['assignee']): ?><div style="font-size:11px;color:var(--text3)">→ <?= ph($t['assignee']) ?></div><?php endif; ?>
            </div>
            <span class="sbadge" style="font-size:10px;background:var(--bg4);color:var(--text3);flex-shrink:0"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span>
            <?php if ($t['due_date']): ?><div style="font-size:11px;color:var(--text3);flex-shrink:0"><?= pDate($t['due_date'],'M j') ?></div><?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <div>
        <div class="pcard">
          <div class="pcard-hd"><div class="pcard-title">Details</div></div>
          <?php foreach ([
            ['Start Date', pDate($proj_det['start_date'])],
            ['Due Date',   pDate($proj_det['due_date'])],
            ['Priority',   ucfirst($proj_det['priority'] ?? '—')],
            ['Budget',     $proj_det['budget'] ? invSym($proj_det['currency'] ?? 'LKR').number_format($proj_det['budget'], 2) : '—'],
          ] as [$l, $v]): ?>
          <div class="detail-row"><span class="detail-lbl"><?= $l ?></span><span class="detail-val"><?= $v ?></span></div>
          <?php endforeach; ?>
        </div>

        <div class="pcard" style="margin-top:0">
          <div class="pcard-hd"><div class="pcard-title">Quick Actions</div></div>
          <a href="client_portal.php?page=messages" class="pbtn pbtn-ghost" style="width:100%;justify-content:center;margin-bottom:8px">💬 Send Message</a>
          <a href="client_portal.php?page=documents" class="pbtn pbtn-ghost" style="width:100%;justify-content:center">📄 View Documents</a>
        </div>
      </div>
    </div>


    <?php // ══════════ INVOICES ══════════
    elseif ($page === 'invoices'):
      $total_paid = array_sum(array_column(array_filter($invoices, fn($i) => $i['status'] === 'paid'), 'total'));
      $total_due  = array_sum(array_column($invoices, 'balance_due'));
    ?>
    <div class="page-hd">
      <div class="page-hd-title">Invoices</div>
      <div class="page-hd-sub">Your billing history from Padak</div>
    </div>

    <div class="pstats">
      <div class="pstat"><span class="pstat-icon">🧾</span><div class="pstat-val"><?= count($invoices) ?></div><div class="pstat-lbl">Total Invoices</div></div>
      <div class="pstat"><span class="pstat-icon">✅</span><div class="pstat-val" style="color:var(--green)">Rs.&nbsp;<?= number_format($total_paid, 0) ?></div><div class="pstat-lbl">Total Paid</div></div>
      <div class="pstat"><span class="pstat-icon">⏳</span><div class="pstat-val" style="color:<?= $total_due > 0 ? 'var(--red)' : 'var(--green)' ?>">Rs.&nbsp;<?= number_format($total_due, 0) ?></div><div class="pstat-lbl">Balance Due</div></div>
    </div>

    <?php if (!$invoices): ?>
    <div class="pcard"><div class="pempty"><div class="pempty-icon">🧾</div><div class="pempty-title">No invoices yet</div><div class="pempty-desc">Invoices from Padak will appear here.</div></div></div>
    <?php else: ?>
    <div class="pcard">
      <div class="pcard-hd"><div class="pcard-title">All Invoices</div></div>
      <?php foreach ($invoices as $inv):
        $ic = invColor($inv['status']);
        if ($inv['status'] === 'draft') continue;
      ?>
      <div class="inv-row">
        <div style="flex:1;min-width:0">
          <div class="inv-num"><?= ph($inv['invoice_no']) ?></div>
          <div class="inv-title"><?= ph($inv['title']) ?></div>
          <div class="inv-dates">Issued <?= pDate($inv['issue_date']) ?><?= $inv['due_date'] ? ' · Due '.pDate($inv['due_date']) : '' ?></div>
        </div>
        <span class="sbadge" style="background:<?= $ic ?>18;color:<?= $ic ?>"><?= ucfirst($inv['status']) ?></span>
        <div class="inv-amount">
          <?= invSym($inv['currency']) ?><?= number_format($inv['total'], 2) ?>
          <?php if ($inv['amount_paid'] > 0): ?><div style="font-size:11.5px;color:var(--green)">Paid: <?= invSym($inv['currency']) ?><?= number_format($inv['amount_paid'], 2) ?></div><?php endif; ?>
          <?php if ($inv['balance_due'] > 0): ?><div style="font-size:11.5px;color:var(--red);font-weight:700">Due: <?= invSym($inv['currency']) ?><?= number_format($inv['balance_due'], 2) ?></div><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>


    <?php // ══════════ DOCUMENTS ══════════
    elseif ($page === 'documents'): ?>
    <div class="page-hd" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px">
      <div>
        <div class="page-hd-title">Documents</div>
        <div class="page-hd-sub">Files shared with you and your uploads</div>
      </div>
      <button class="pbtn pbtn-primary" onclick="document.getElementById('upload-panel').style.display=document.getElementById('upload-panel').style.display==='none'?'block':'none'">
        ↑ Upload File
      </button>
    </div>

    <!-- Upload panel -->
    <div id="upload-panel" style="display:none" class="upload-panel">
      <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:16px">Upload a File</div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_doc">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px" class="upload-grid">
          <div class="pform-group">
            <label class="pform-label">Title</label>
            <input type="text" name="title" class="pform-control" placeholder="File title">
          </div>
          <div class="pform-group">
            <label class="pform-label">Link to Project</label>
            <select name="project_id" class="pform-control">
              <option value="">— None —</option>
              <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>"><?= ph($p['title']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="pform-group" style="grid-column:1/-1">
            <label class="pform-label">File <span style="font-weight:400;text-transform:none;font-size:11px;color:var(--text3)">(PDF, Word, Excel, Images, ZIP — max 20MB)</span></label>
            <input type="file" name="file" class="pform-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.txt,.zip" required>
          </div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:4px">
          <button type="button" class="pbtn pbtn-ghost" onclick="document.getElementById('upload-panel').style.display='none'">Cancel</button>
          <button type="submit" class="pbtn pbtn-primary">Upload →</button>
        </div>
      </form>
    </div>

    <?php if (!$docs): ?>
    <div class="pcard"><div class="pempty"><div class="pempty-icon">📄</div><div class="pempty-title">No documents yet</div><div class="pempty-desc">Files shared by the Padak team and your uploads will appear here.</div></div></div>
    <?php else: ?>
    <div class="pcard">
      <div class="pcard-hd"><div class="pcard-title"><?= count($docs) ?> File<?= count($docs) != 1 ? 's' : '' ?></div></div>
      <?php foreach ($docs as $d):
        [$icon, $iconBg, $iconColor] = docIcon($d['original_name']);
        $ext = strtolower(pathinfo($d['original_name'], PATHINFO_EXTENSION));
      ?>
      <div class="doc-row">
        <div class="doc-icon" style="background:<?= $iconBg ?>;color:<?= $iconColor ?>"><?= $icon ?></div>
        <div style="flex:1;min-width:0">
          <div class="doc-name"><?= ph($d['title']) ?></div>
          <div class="doc-meta"><?= ph($d['original_name']) ?> · <?= round($d['file_size']/1024) ?> KB · <?= pDate($d['created_at']) ?></div>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0">
          <?php if ($ext === 'pdf'): ?>
          <a href="client_portal.php?view_doc=<?= $d['id'] ?>" target="_blank" class="doc-btn" title="Preview PDF">👁</a>
          <?php endif; ?>
          <a href="client_portal.php?dl_doc=<?= $d['id'] ?>" class="doc-btn" title="Download">⬇</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>


    <?php // ══════════ MESSAGES ══════════
    elseif ($page === 'messages'): ?>
    <div class="page-hd">
      <div class="page-hd-title">Messages</div>
      <div class="page-hd-sub">Direct communication with the Padak team</div>
    </div>

    <!-- Compose -->
    <div class="compose-box">
      <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:16px">✍ New Message</div>
      <form method="POST">
        <input type="hidden" name="action" value="send_message">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px" class="upload-grid">
          <div class="pform-group" style="margin-bottom:0">
            <label class="pform-label">Subject</label>
            <input type="text" name="subject" class="pform-control" placeholder="What's this about?">
          </div>
          <div class="pform-group" style="margin-bottom:0">
            <label class="pform-label">Related Project</label>
            <select name="project_id" class="pform-control">
              <option value="">— General —</option>
              <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>"><?= ph($p['title']) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="pform-group">
          <label class="pform-label">Message</label>
          <textarea name="body" class="pform-control" style="min-height:80px;resize:vertical" placeholder="Type your message here…" required></textarea>
        </div>
        <div style="display:flex;justify-content:flex-end">
          <button type="submit" class="pbtn pbtn-primary">Send Message →</button>
        </div>
      </form>
    </div>

    <!-- Thread -->
    <div class="pcard">
      <div class="pcard-hd"><div class="pcard-title">Message History</div></div>
      <?php if (!$messages): ?>
      <div class="pempty"><div class="pempty-icon">💬</div><div class="pempty-title">No messages yet</div><div class="pempty-desc">Send a message above to start a conversation with the Padak team.</div></div>
      <?php else: ?>
      <div id="msg-thread" style="display:flex;flex-direction:column;gap:0;max-height:540px;overflow-y:auto;padding:8px 4px">
        <?php foreach (array_reverse($messages) as $m):
          $is_mine = $m['sender_type'] === 'client';
        ?>
        <div class="msg-wrap <?= $is_mine ? 'mine' : '' ?>">
          <div class="msg-inner">
            <div class="msg-sender">
              <?php if (!$is_mine): ?><?= ph($m['staff_name'] ?? 'Padak Team') ?><?= $m['project_title'] ? ' · '.ph($m['project_title']) : '' ?><?php else: ?><?= ph($client['name']) ?><?php endif; ?>
              <?php if ($m['subject']): ?> · <em><?= ph($m['subject']) ?></em><?php endif; ?>
            </div>
            <div class="msg-bubble <?= $is_mine ? 'msg-mine' : 'msg-staff' ?>"><?= nl2br(ph($m['body'])) ?></div>
            <div class="msg-time"><?= pDate($m['created_at'], 'M j, g:ia') ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <?php endif; // end page routing ?>

    </div><!-- end portal-content -->
  </main>
</div><!-- end portal-wrap -->
<?php endif; // end login vs authenticated ?>

<script>
// ── Theme Toggle ─────────────────────────────────────────────────
function portalToggleTheme() {
    var cur  = document.documentElement.getAttribute('data-theme') || 'dark';
    var next = cur === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('padak_portal_theme', next);
    updateThemeIcons(next);
}
function updateThemeIcons(theme) {
    var icon = theme === 'dark' ? '🌙' : '☀️';
    ['login-theme-btn','sidebar-theme-btn'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.textContent = icon;
    });
}
// Set correct icon on load
(function(){
    var t = localStorage.getItem('padak_portal_theme') || 'dark';
    updateThemeIcons(t);
})();

// ── Mobile Sidebar ───────────────────────────────────────────────
function portalToggleSidebar() {
    var sb  = document.getElementById('portal-sidebar');
    var ov  = document.getElementById('sidebar-overlay');
    if (!sb) return;
    var open = sb.classList.toggle('open');
    if (ov) ov.classList.toggle('open', open);
}

// ── Auto-scroll messages ─────────────────────────────────────────
var mt = document.getElementById('msg-thread');
if (mt) mt.scrollTop = mt.scrollHeight;

// ── Drag-and-drop upload area ─────────────────────────────────────
(function(){
    var panel = document.getElementById('upload-panel');
    if (!panel) return;
    ['dragenter','dragover'].forEach(function(ev){
        panel.addEventListener(ev, function(e){ e.preventDefault(); panel.classList.add('drag-over'); });
    });
    ['dragleave','drop'].forEach(function(ev){
        panel.addEventListener(ev, function(){ panel.classList.remove('drag-over'); });
    });
    panel.addEventListener('drop', function(e){
        e.preventDefault();
        var fi = panel.querySelector('input[type=file]');
        if (fi && e.dataTransfer.files.length) fi.files = e.dataTransfer.files;
    });
})();
</script>
</body>
</html>