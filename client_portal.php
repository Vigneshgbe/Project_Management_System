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
// POST HANDLERS
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
    // fall through to show login with error
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
                $title = trim($_POST['title'] ?? '') ?: basename($f['name']);
                $pid   = (int)($_POST['project_id'] ?? 0) ?: null;
                $uid   = $client['contact_id'];
                $oname = basename($f['name']);
                $fsize = (int)$f['size'];
                $ftype = $f['type'];
                $stmt  = $db->prepare("INSERT INTO documents (title,filename,original_name,file_size,file_type,project_id,contact_id,category,access,uploaded_by) VALUES (?,?,?,?,?,?,'portal','client_upload','all',?)");
                // uploaded_by is a user id - use contact_id mapped, just store 0 or first admin
                $admin = $db->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetch_assoc();
                $admin_id = $admin ? (int)$admin['id'] : 1;
                $stmt2 = $db->prepare("INSERT INTO documents (title,filename,original_name,file_size,file_type,contact_id,category,access,uploaded_by) VALUES (?,?,?,?,?,?,?,?,?)");
                $cat   = 'Client Upload';
                $access= 'manager';
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
// ROUTE — require login for all except login page
// ══════════════════════════════════════════
$client = portalClient();
$page   = $_GET['page'] ?? ($client ? 'dashboard' : 'login');
if (!$client && $page !== 'login') $page = 'login';

// ── LOAD CLIENT DATA ──
$projects  = [];
$invoices  = [];
$messages  = [];
$docs      = [];
$proj_det  = null;

if ($client) {
    $cid = $client['contact_id'];

    // Projects linked to this contact
    $projects = $db->query("
        SELECT p.*, u.name AS pm_name,
            (SELECT COUNT(*) FROM tasks t WHERE t.project_id=p.id AND t.status='done') AS done_tasks,
            (SELECT COUNT(*) FROM tasks t WHERE t.project_id=p.id) AS total_tasks
        FROM projects p
        LEFT JOIN users u ON u.id=p.created_by
        WHERE p.contact_id=$cid
        ORDER BY p.created_at DESC
    ")->fetch_all(MYSQLI_ASSOC);

    // Invoices
    $invoices = $db->query("
        SELECT i.*, COALESCE(i.total-i.amount_paid,0) AS balance_due
        FROM invoices i
        WHERE i.contact_id=$cid
        ORDER BY i.issue_date DESC
    ")->fetch_all(MYSQLI_ASSOC);

    // Messages
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

    // Documents (uploaded by team or client for this contact)
    $docs = $db->query("
        SELECT d.*, u.name AS uploader
        FROM documents d
        LEFT JOIN users u ON u.id=d.uploaded_by
        WHERE d.contact_id=$cid OR d.project_id IN (SELECT id FROM projects WHERE contact_id=$cid)
        ORDER BY d.created_at DESC
    ")->fetch_all(MYSQLI_ASSOC);

    // Mark staff messages as read
    if ($page === 'messages') {
        $db->query("UPDATE client_messages SET is_read=1 WHERE contact_id=$cid AND sender_type='staff' AND is_read=0");
    }

    // Single project detail
    if ($page === 'project' && isset($_GET['id'])) {
        $pid = (int)$_GET['id'];
        $r = $db->query("SELECT * FROM projects WHERE id=$pid AND contact_id=$cid")->fetch_assoc();
        if ($r) {
            $proj_det = $r;
            $proj_det['tasks'] = $db->query("SELECT t.*,u.name AS assignee FROM tasks t LEFT JOIN users u ON u.id=t.assigned_to WHERE t.project_id=$pid ORDER BY FIELD(t.status,'todo','in_progress','review','done'), t.due_date")->fetch_all(MYSQLI_ASSOC);
        }
    }

    // Unread count for badge
    $unread = (int)$db->query("SELECT COUNT(*) AS c FROM client_messages WHERE contact_id=$cid AND sender_type='staff' AND is_read=0")->fetch_assoc()['c'];
}

// ── PDF INLINE SERVE ──
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
        $path = UPLOAD_DOC_DIR . $doc['filename'];
        if (!file_exists($path)) $path = UPLOAD_DOC_DIR . $doc['filename'];
        // Try main doc dir too
        if (!file_exists($path)) $path = UPLOAD_DOC_DIR . $doc['filename'];
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
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Bricolage+Grotesque:wght@600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0f1117;--bg2:#161b27;--bg3:#1e2538;--bg4:#252d40;
  --border:#2a3348;--border2:#3a4560;
  --text:#e8eaf0;--text2:#9aa3b8;--text3:#5a6478;
  --orange:#f97316;--orange-bg:rgba(249,115,22,.1);
  --green:#10b981;--red:#ef4444;--blue:#6366f1;--yellow:#f59e0b;
  --radius:10px;--radius-sm:6px;--radius-lg:16px;
  --font:'Plus Jakarta Sans',sans-serif;--font-d:'Bricolage Grotesque',sans-serif;
  --sidebar:240px;
}
[data-theme="light"]{
  --bg:#f0f2f7;--bg2:#fff;--bg3:#f5f6fa;--bg4:#eaecf2;
  --border:#dde1ec;--border2:#c8cdde;--text:#1e2538;--text2:#4a5568;--text3:#9aa3b8;
}
body{font-family:var(--font);background:var(--bg);color:var(--text);min-height:100vh}
a{color:inherit;text-decoration:none}
button{cursor:pointer;font-family:var(--font)}
input,select,textarea{font-family:var(--font)}

/* ── LOGIN PAGE ── */
.portal-login{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;background:var(--bg)}
.login-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:40px;width:100%;max-width:400px}
.login-brand{text-align:center;margin-bottom:32px}
.login-brand-name{font-family:var(--font-d);font-size:26px;font-weight:700;color:var(--text)}
.login-brand-name span{color:var(--orange)}
.login-brand-sub{font-size:13px;color:var(--text3);margin-top:4px}
.form-field{margin-bottom:16px}
.form-field label{display:block;font-size:12.5px;font-weight:600;color:var(--text2);margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em}
.form-field input{width:100%;padding:11px 14px;background:var(--bg3);border:1.5px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:14px;transition:border-color .15s;outline:none}
.form-field input:focus{border-color:var(--orange)}
.btn-login{width:100%;padding:12px;background:var(--orange);color:#fff;border:none;border-radius:var(--radius-sm);font-size:14px;font-weight:700;cursor:pointer;margin-top:6px}
.btn-login:hover{opacity:.9}
.login-error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:var(--red);padding:10px 14px;border-radius:var(--radius-sm);font-size:13px;margin-bottom:16px}

/* ── PORTAL LAYOUT ── */
.portal-wrap{display:flex;min-height:100vh}
.portal-sidebar{width:var(--sidebar);min-width:var(--sidebar);background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh}
.portal-brand{padding:20px 18px 16px;border-bottom:1px solid var(--border);flex-shrink:0}
.portal-brand-name{font-family:var(--font-d);font-size:18px;font-weight:700}
.portal-brand-name span{color:var(--orange)}
.portal-brand-sub{font-size:11px;color:var(--text3);margin-top:2px}
.portal-nav{flex:1;padding:10px 8px;overflow-y:auto}
.pnav-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:var(--radius-sm);cursor:pointer;color:var(--text2);font-size:13px;font-weight:500;transition:background .12s,color .12s;white-space:nowrap}
.pnav-item:hover{background:var(--bg3);color:var(--text)}
.pnav-item.active{background:var(--orange-bg);color:var(--orange);font-weight:700}
.pnav-icon{font-size:16px;flex-shrink:0;width:20px;text-align:center}
.pnav-badge{margin-left:auto;background:var(--red);color:#fff;font-size:9px;font-weight:800;padding:1px 5px;border-radius:99px}
.portal-user{padding:14px 16px;border-top:1px solid var(--border);flex-shrink:0}
.portal-user-name{font-size:13px;font-weight:700;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.portal-user-co{font-size:11px;color:var(--text3)}
.portal-main{flex:1;margin-left:var(--sidebar);padding:28px 32px;max-width:1100px}
.portal-header{margin-bottom:24px}
.portal-page-title{font-family:var(--font-d);font-size:22px;font-weight:700;margin-bottom:4px}
.portal-page-sub{font-size:13px;color:var(--text3)}

/* ── CARDS ── */
.pcard{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;margin-bottom:14px}
.pcard-title{font-size:13px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:14px}

/* ── STATS ROW ── */
.pstats{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:20px}
.pstat{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px}
.pstat-val{font-size:22px;font-weight:800;font-family:var(--font-d);color:var(--text);margin-bottom:2px}
.pstat-lbl{font-size:11.5px;color:var(--text3);text-transform:uppercase;letter-spacing:.04em}

/* ── PROJECT CARD ── */
.proj-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;margin-bottom:12px;cursor:pointer;transition:border-color .15s,box-shadow .15s}
.proj-card:hover{border-color:var(--border2);box-shadow:0 2px 10px rgba(0,0,0,.2)}
.proj-progress-bar{height:6px;background:var(--bg4);border-radius:99px;overflow:hidden;margin:10px 0 6px}
.proj-progress-fill{height:100%;background:linear-gradient(90deg,var(--orange),#f59e0b);border-radius:99px;transition:width .4s}

/* ── STATUS BADGE ── */
.sbadge{display:inline-flex;align-items:center;padding:2px 9px;border-radius:99px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}

/* ── TASK ROW ── */
.task-row{display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--bg3);border-radius:var(--radius-sm);margin-bottom:6px}
.task-status-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}

/* ── INVOICE ROW ── */
.inv-row{display:flex;align-items:center;gap:14px;padding:13px 16px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:7px}
.inv-row:hover{border-color:var(--border2)}

/* ── MESSAGE ── */
.msg-bubble{max-width:72%;padding:11px 14px;border-radius:12px;font-size:13.5px;line-height:1.55;word-break:break-word}
.msg-mine{background:var(--orange);color:#fff;margin-left:auto;border-bottom-right-radius:3px}
.msg-staff{background:var(--bg3);border:1px solid var(--border);color:var(--text);border-bottom-left-radius:3px}
.msg-wrap{display:flex;margin-bottom:12px}
.msg-wrap.mine{justify-content:flex-end}

/* ── DOC ROW ── */
.doc-row{display:flex;align-items:center;gap:12px;padding:11px 14px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:7px}

/* ── FORM ── */
.pform-control{width:100%;padding:10px 13px;background:var(--bg3);border:1.5px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:13.5px;outline:none;transition:border-color .15s}
.pform-control:focus{border-color:var(--orange)}
.pform-label{display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:5px;text-transform:uppercase;letter-spacing:.04em}
.pbtn{padding:9px 18px;border-radius:var(--radius-sm);font-size:13px;font-weight:700;border:none;cursor:pointer;transition:opacity .15s}
.pbtn-primary{background:var(--orange);color:#fff}
.pbtn-primary:hover{opacity:.9}
.pbtn-ghost{background:var(--bg3);color:var(--text2);border:1px solid var(--border)}
.pbtn-ghost:hover{background:var(--bg4);color:var(--text)}

/* Mobile */
@media(max-width:768px){
  .portal-sidebar{transform:translateX(-100%);transition:transform .25s;z-index:100}
  .portal-sidebar.open{transform:none}
  .portal-main{margin-left:0;padding:16px}
  .mob-toggle{display:flex}
}
.mob-toggle{display:none;position:fixed;top:14px;left:14px;z-index:200;background:var(--orange);color:#fff;border:none;border-radius:8px;padding:8px 10px;font-size:16px}
</style>
</head>
<body>

<?php if ($page === 'login'): ?>
<!-- ════════════════════ LOGIN PAGE ════════════════════ -->
<div class="portal-login">
  <div class="login-card">
    <div class="login-brand">
      <div class="login-brand-name">Padak <span>CRM</span></div>
      <div class="login-brand-sub">Client Portal — Secure Access</div>
    </div>
    <?php if (!empty($show_login_error)): ?>
    <div class="login-error">⚠ <?= ph($show_login_error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="action" value="login">
      <div class="form-field">
        <label>Email Address</label>
        <input type="email" name="email" required autofocus placeholder="your@email.com">
      </div>
      <div class="form-field">
        <label>Password</label>
        <input type="password" name="password" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn-login">Sign In →</button>
    </form>
    <div style="text-align:center;margin-top:20px;font-size:12px;color:var(--text3)">
      Don't have access? Contact <a href="mailto:careers@thepadak.com" style="color:var(--orange)">Padak team</a>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ════════════════════ AUTHENTICATED PORTAL ════════════════════ -->
<button class="mob-toggle" onclick="document.querySelector('.portal-sidebar').classList.toggle('open')">☰</button>

<div class="portal-wrap">
  <!-- SIDEBAR -->
  <aside class="portal-sidebar">
    <div class="portal-brand">
      <div class="portal-brand-name">Padak <span>CRM</span></div>
      <div class="portal-brand-sub">Client Portal</div>
    </div>
    <nav class="portal-nav">
      <?php
      $navItems = [
        ['dashboard', '📊', 'Dashboard'],
        ['projects',  '📁', 'My Projects'],
        ['invoices',  '🧾', 'Invoices'],
        ['documents', '📄', 'Documents'],
        ['messages',  '💬', 'Messages', $unread],
      ];
      foreach ($navItems as $ni):
        $act = $page === $ni[0] ? ' active' : '';
      ?>
      <a href="client_portal.php?page=<?= $ni[0] ?>" class="pnav-item<?= $act ?>">
        <span class="pnav-icon"><?= $ni[1] ?></span>
        <?= $ni[2] ?>
        <?php if (!empty($ni[3])): ?><span class="pnav-badge"><?= $ni[3] ?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </nav>
    <div class="portal-user">
      <div class="portal-user-name"><?= ph($client['name']) ?></div>
      <div class="portal-user-co"><?= ph($client['company'] ?: $client['email']) ?></div>
      <a href="client_portal.php?logout=1" style="font-size:12px;color:var(--text3);margin-top:6px;display:block">Sign out</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="portal-main">

  <?php
  // ── PROJECT STATUS COLOR ──
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
          'done'       => 'var(--green)',
          'in_progress'=> 'var(--orange)',
          'review'     => 'var(--blue)',
          default      => 'var(--text3)',
      };
  }
  function invColor(string $s): string {
      return match($s) {
          'paid'=>'#10b981','partial'=>'#f59e0b','overdue'=>'#ef4444',
          'sent'=>'#6366f1','viewed'=>'#8b5cf6','draft'=>'#94a3b8',
          default=>'#94a3b8'
      };
  }
  function docIcon(string $name): string {
      $ext = strtolower(pathinfo($name,PATHINFO_EXTENSION));
      return match(true) {
          $ext==='pdf'                                    => '📕',
          in_array($ext,['doc','docx'])                  => '📘',
          in_array($ext,['xls','xlsx'])                  => '📗',
          in_array($ext,['jpg','jpeg','png','gif','webp'])=> '🖼',
          default                                         => '📄'
      };
  }
  ?>

  <?php // ══════════════════════════ DASHBOARD ══════════════════════════
  if ($page === 'dashboard'):
    $active_proj = array_filter($projects, fn($p) => $p['status']==='active');
    $open_inv    = array_filter($invoices, fn($i) => !in_array($i['status'],['paid','cancelled']));
    $total_due   = array_sum(array_column(iterator_to_array((function($a){foreach($a as $v)yield $v;})(array_filter($invoices,fn($i)=>$i['status']!=='paid'))),'balance_due'));
  ?>
  <div class="portal-header">
    <div class="portal-page-title">Welcome back, <?= ph(explode(' ',$client['name'])[0]) ?> 👋</div>
    <div class="portal-page-sub"><?= ph($client['company'] ?: '') ?> · <?= date('l, F j, Y') ?></div>
  </div>

  <div class="pstats">
    <div class="pstat"><div class="pstat-val"><?= count($projects) ?></div><div class="pstat-lbl">Total Projects</div></div>
    <div class="pstat"><div class="pstat-val"><?= count($active_proj) ?></div><div class="pstat-lbl">Active Now</div></div>
    <div class="pstat"><div class="pstat-val"><?= count($open_inv) ?></div><div class="pstat-lbl">Open Invoices</div></div>
    <div class="pstat"><div class="pstat-val" style="color:var(--<?= $total_due>0?'red':'green' ?>)">Rs. <?= number_format($total_due,0) ?></div><div class="pstat-lbl">Balance Due</div></div>
  </div>

  <?php if ($projects): ?>
  <div class="pcard">
    <div class="pcard-title">Recent Projects</div>
    <?php foreach (array_slice($projects,0,3) as $p):
      [$pc,$pbg] = projColor($p['status']);
      $pct = (int)$p['progress'];
    ?>
    <div class="proj-card" onclick="location.href='client_portal.php?page=project&id=<?= $p['id'] ?>'">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div style="font-size:14px;font-weight:700;flex:1;margin-right:10px"><?= ph($p['title']) ?></div>
        <span class="sbadge" style="background:<?= $pbg ?>;color:<?= $pc ?>;flex-shrink:0"><?= ucfirst(str_replace('_',' ',$p['status'])) ?></span>
      </div>
      <div class="proj-progress-bar"><div class="proj-progress-fill" style="width:<?= $pct ?>%"></div></div>
      <div style="display:flex;justify-content:space-between;font-size:11.5px;color:var(--text3)">
        <span><?= $pct ?>% complete · <?= $p['done_tasks'] ?>/<?= $p['total_tasks'] ?> tasks</span>
        <span><?= $p['due_date'] ? 'Due '.pDate($p['due_date']) : 'No deadline' ?></span>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (count($projects)>3): ?><a href="client_portal.php?page=projects" style="font-size:12.5px;color:var(--orange)">View all <?= count($projects) ?> projects →</a><?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if ($invoices): ?>
  <div class="pcard">
    <div class="pcard-title">Recent Invoices</div>
    <?php foreach (array_slice($invoices,0,3) as $inv):
      $ic = invColor($inv['status']);
    ?>
    <div class="inv-row">
      <div style="flex:1;min-width:0">
        <div style="font-size:13px;font-weight:700"><?= ph($inv['invoice_no']) ?> — <?= ph($inv['title']) ?></div>
        <div style="font-size:11.5px;color:var(--text3)">Due <?= pDate($inv['due_date']) ?></div>
      </div>
      <span class="sbadge" style="background:<?= $ic ?>18;color:<?= $ic ?>"><?= ucfirst($inv['status']) ?></span>
      <div style="text-align:right;min-width:90px">
        <div style="font-size:14px;font-weight:700"><?= invSym($inv['currency']) ?><?= number_format($inv['total'],2) ?></div>
        <?php if ($inv['balance_due']>0): ?><div style="font-size:11px;color:var(--red);font-weight:600">Due: <?= invSym($inv['currency']) ?><?= number_format($inv['balance_due'],2) ?></div><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($unread > 0): ?>
  <div class="pcard" style="border-color:var(--orange);background:var(--orange-bg)">
    <div style="display:flex;justify-content:space-between;align-items:center">
      <div><div style="font-weight:700;color:var(--orange)">💬 <?= $unread ?> new message<?= $unread>1?'s':'' ?> from Padak team</div></div>
      <a href="client_portal.php?page=messages" class="pbtn pbtn-primary">View →</a>
    </div>
  </div>
  <?php endif; ?>


  <?php // ══════════════════════════ PROJECTS LIST ══════════════════════════
  elseif ($page === 'projects'): ?>
  <div class="portal-header"><div class="portal-page-title">My Projects</div><div class="portal-page-sub"><?= count($projects) ?> project<?= count($projects)!=1?'s':'' ?> linked to your account</div></div>
  <?php if (!$projects): ?>
  <div class="pcard" style="text-align:center;padding:40px;color:var(--text3)"><div style="font-size:32px;margin-bottom:8px">📁</div><div>No projects yet.</div></div>
  <?php else: ?>
  <?php foreach ($projects as $p):
    [$pc,$pbg] = projColor($p['status']);
    $pct = (int)$p['progress'];
  ?>
  <div class="proj-card" onclick="location.href='client_portal.php?page=project&id=<?= $p['id'] ?>'">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
      <div>
        <div style="font-size:15px;font-weight:700;margin-bottom:3px"><?= ph($p['title']) ?></div>
        <div style="font-size:12px;color:var(--text3)">Managed by <?= ph($p['pm_name']??'Padak') ?><?= $p['due_date'] ? ' · Due '.pDate($p['due_date']) : '' ?></div>
      </div>
      <span class="sbadge" style="background:<?= $pbg ?>;color:<?= $pc ?>;flex-shrink:0"><?= ucfirst(str_replace('_',' ',$p['status'])) ?></span>
    </div>
    <div class="proj-progress-bar"><div class="proj-progress-fill" style="width:<?= $pct ?>%"></div></div>
    <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text3)">
      <span><?= $pct ?>% complete</span>
      <span><?= $p['done_tasks'] ?>/<?= $p['total_tasks'] ?> tasks done</span>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>


  <?php // ══════════════════════════ SINGLE PROJECT ══════════════════════════
  elseif ($page === 'project' && $proj_det): ?>
  <div style="margin-bottom:16px"><a href="client_portal.php?page=projects" style="color:var(--text3);font-size:13px">← All Projects</a></div>
  <?php [$pc,$pbg] = projColor($proj_det['status']); $pct = (int)$proj_det['progress']; ?>
  <div class="portal-header">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px">
      <div>
        <div class="portal-page-title"><?= ph($proj_det['title']) ?></div>
        <div class="portal-page-sub"><?= ph($proj_det['description']??'') ?></div>
      </div>
      <span class="sbadge" style="background:<?= $pbg ?>;color:<?= $pc ?>;font-size:13px;padding:5px 14px"><?= ucfirst(str_replace('_',' ',$proj_det['status'])) ?></span>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;align-items:start">
    <div>
      <!-- Progress -->
      <div class="pcard">
        <div class="pcard-title">Progress — <?= $pct ?>%</div>
        <div class="proj-progress-bar" style="height:10px"><div class="proj-progress-fill" style="width:<?= $pct ?>%"></div></div>
        <div style="font-size:12px;color:var(--text3);margin-top:6px"><?= ($proj_det['tasks'] ? array_reduce($proj_det['tasks'],fn($c,$t)=>$c+($t['status']==='done'?1:0),0) : 0) ?>/<?= count($proj_det['tasks']??[]) ?> tasks completed</div>
      </div>

      <!-- Tasks -->
      <?php if ($proj_det['tasks']): ?>
      <div class="pcard">
        <div class="pcard-title">Task Overview</div>
        <?php foreach ($proj_det['tasks'] as $t): ?>
        <div class="task-row">
          <div class="task-status-dot" style="background:<?= taskDot($t['status']) ?>"></div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis<?= $t['status']==='done'?';text-decoration:line-through;opacity:.6':'' ?>"><?= ph($t['title']) ?></div>
            <?php if ($t['assignee']): ?><div style="font-size:11px;color:var(--text3)">→ <?= ph($t['assignee']) ?></div><?php endif; ?>
          </div>
          <span class="sbadge" style="font-size:10px;background:var(--bg4);color:var(--text3)"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span>
          <?php if ($t['due_date']): ?><div style="font-size:11px;color:var(--text3);flex-shrink:0"><?= pDate($t['due_date'],'M j') ?></div><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div>
      <div class="pcard">
        <div class="pcard-title">Details</div>
        <?php foreach ([
          ['Start Date', pDate($proj_det['start_date'])],
          ['Due Date',   pDate($proj_det['due_date'])],
          ['Priority',   ucfirst($proj_det['priority'])],
          ['Budget',     $proj_det['budget'] ? invSym($proj_det['currency']).number_format($proj_det['budget'],2) : '—'],
        ] as [$l,$v]): ?>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:12.5px">
          <span style="color:var(--text3)"><?= $l ?></span>
          <span style="font-weight:600;color:var(--text2)"><?= $v ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>


  <?php // ══════════════════════════ INVOICES ══════════════════════════
  elseif ($page === 'invoices'):
    $total_paid = array_sum(array_column(array_filter($invoices,fn($i)=>$i['status']==='paid'),'total'));
    $total_due  = array_sum(array_column($invoices,'balance_due'));
  ?>
  <div class="portal-header"><div class="portal-page-title">Invoices</div><div class="portal-page-sub">Your billing history from Padak</div></div>

  <div class="pstats">
    <div class="pstat"><div class="pstat-val"><?= count($invoices) ?></div><div class="pstat-lbl">Total Invoices</div></div>
    <div class="pstat"><div class="pstat-val" style="color:var(--green)">Rs. <?= number_format($total_paid,0) ?></div><div class="pstat-lbl">Total Paid</div></div>
    <div class="pstat"><div class="pstat-val" style="color:<?= $total_due>0?'var(--red)':'var(--green)' ?>">Rs. <?= number_format($total_due,0) ?></div><div class="pstat-lbl">Balance Due</div></div>
  </div>

  <?php if (!$invoices): ?>
  <div class="pcard" style="text-align:center;padding:40px;color:var(--text3)"><div style="font-size:32px;margin-bottom:8px">🧾</div><div>No invoices yet.</div></div>
  <?php else: ?>
  <?php foreach ($invoices as $inv):
    $ic = invColor($inv['status']);
    // Only show sent/viewed/partial/paid/overdue — hide drafts
    if ($inv['status'] === 'draft') continue;
  ?>
  <div class="inv-row">
    <div style="flex:1;min-width:0">
      <div style="font-size:13.5px;font-weight:700"><?= ph($inv['invoice_no']) ?></div>
      <div style="font-size:12px;color:var(--text2);margin-top:1px"><?= ph($inv['title']) ?></div>
      <div style="font-size:11.5px;color:var(--text3)">Issued <?= pDate($inv['issue_date']) ?><?= $inv['due_date'] ? ' · Due '.pDate($inv['due_date']) : '' ?></div>
    </div>
    <span class="sbadge" style="background:<?= $ic ?>18;color:<?= $ic ?>"><?= ucfirst($inv['status']) ?></span>
    <div style="text-align:right;min-width:100px">
      <div style="font-size:15px;font-weight:800;color:var(--text)"><?= invSym($inv['currency']) ?><?= number_format($inv['total'],2) ?></div>
      <?php if ($inv['amount_paid']>0): ?><div style="font-size:11.5px;color:var(--green)">Paid: <?= invSym($inv['currency']) ?><?= number_format($inv['amount_paid'],2) ?></div><?php endif; ?>
      <?php if ($inv['balance_due']>0): ?><div style="font-size:11.5px;color:var(--red);font-weight:700">Due: <?= invSym($inv['currency']) ?><?= number_format($inv['balance_due'],2) ?></div><?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>


  <?php // ══════════════════════════ DOCUMENTS ══════════════════════════
  elseif ($page === 'documents'): ?>
  <div class="portal-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px">
    <div><div class="portal-page-title">Documents</div><div class="portal-page-sub">Files shared with you and your uploads</div></div>
    <button class="pbtn pbtn-primary" onclick="document.getElementById('upload-panel').style.display=document.getElementById('upload-panel').style.display==='none'?'block':'none'">↑ Upload File</button>
  </div>

  <!-- Upload panel (toggle) -->
  <div id="upload-panel" style="display:none" class="pcard">
    <div class="pcard-title">Upload a File</div>
    <form method="POST" enctype="multipart/form-data" style="display:grid;grid-template-columns:1fr 1fr auto;gap:10px;align-items:end">
      <input type="hidden" name="action" value="upload_doc">
      <div><label class="pform-label">Title</label><input type="text" name="title" class="pform-control" placeholder="File title"></div>
      <div><label class="pform-label">Link to Project</label>
        <select name="project_id" class="pform-control">
          <option value="">— None —</option>
          <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>"><?= ph($p['title']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div><label class="pform-label">File</label><input type="file" name="file" class="pform-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.txt,.zip" required></div>
      <div style="grid-column:1/-1;display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="pbtn pbtn-ghost" onclick="document.getElementById('upload-panel').style.display='none'">Cancel</button>
        <button type="submit" class="pbtn pbtn-primary">Upload →</button>
      </div>
    </form>
  </div>

  <?php if (!$docs): ?>
  <div class="pcard" style="text-align:center;padding:40px;color:var(--text3)"><div style="font-size:32px;margin-bottom:8px">📄</div><div>No documents yet.</div></div>
  <?php else: ?>
  <?php foreach ($docs as $d):
    $ext = strtolower(pathinfo($d['original_name'],PATHINFO_EXTENSION));
  ?>
  <div class="doc-row">
    <span style="font-size:26px;flex-shrink:0"><?= docIcon($d['original_name']) ?></span>
    <div style="flex:1;min-width:0">
      <div style="font-size:13.5px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= ph($d['title']) ?></div>
      <div style="font-size:11.5px;color:var(--text3)"><?= ph($d['original_name']) ?> · <?= round($d['file_size']/1024) ?> KB · <?= pDate($d['created_at']) ?></div>
    </div>
    <div style="display:flex;gap:6px;flex-shrink:0">
      <?php if ($ext==='pdf'): ?>
      <a href="client_portal.php?view_doc=<?= $d['id'] ?>" target="_blank" class="pbtn pbtn-ghost" style="padding:6px 10px;font-size:13px" title="Preview">👁</a>
      <?php endif; ?>
      <a href="client_portal.php?dl_doc=<?= $d['id'] ?>" class="pbtn pbtn-ghost" style="padding:6px 10px;font-size:13px" title="Download">↓</a>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>


  <?php // ══════════════════════════ MESSAGES ══════════════════════════
  elseif ($page === 'messages'): ?>
  <div class="portal-header"><div class="portal-page-title">Messages</div><div class="portal-page-sub">Direct communication thread with Padak team</div></div>

  <!-- Compose -->
  <div class="pcard" style="margin-bottom:20px">
    <div class="pcard-title">New Message</div>
    <form method="POST">
      <input type="hidden" name="action" value="send_message">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
        <div><label class="pform-label">Subject</label><input type="text" name="subject" class="pform-control" placeholder="What's this about?"></div>
        <div><label class="pform-label">Related Project</label>
          <select name="project_id" class="pform-control">
            <option value="">— General —</option>
            <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>"><?= ph($p['title']) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div style="margin-bottom:10px"><label class="pform-label">Message</label><textarea name="body" class="pform-control" style="min-height:80px;resize:vertical" placeholder="Type your message…" required></textarea></div>
      <div style="display:flex;justify-content:flex-end"><button type="submit" class="pbtn pbtn-primary">Send Message →</button></div>
    </form>
  </div>

  <!-- Thread -->
  <div class="pcard">
    <div class="pcard-title">Message History</div>
    <?php if (!$messages): ?>
    <div style="text-align:center;padding:30px;color:var(--text3)"><div style="font-size:28px;margin-bottom:8px">💬</div><div>No messages yet. Send one above!</div></div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:0;max-height:520px;overflow-y:auto;padding:4px 0">
      <?php foreach (array_reverse($messages) as $m):
        $is_mine = $m['sender_type'] === 'client';
      ?>
      <div class="msg-wrap <?= $is_mine ? 'mine' : '' ?>">
        <div>
          <?php if (!$is_mine): ?><div style="font-size:11px;color:var(--text3);margin-bottom:3px;padding-left:4px"><?= ph($m['staff_name']??'Padak Team') ?><?= $m['subject'] ? ' · '.ph($m['subject']) : '' ?></div><?php endif; ?>
          <div class="msg-bubble <?= $is_mine?'msg-mine':'msg-staff' ?>"><?= nl2br(ph($m['body'])) ?></div>
          <div style="font-size:10.5px;color:var(--text3);margin-top:3px;<?= $is_mine?'text-align:right':'padding-left:4px' ?>"><?= pDate($m['created_at'],'M j, g:ia') ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <?php endif; // end page routing ?>

  </main>
</div><!-- end portal-wrap -->

<?php endif; // end login vs portal ?>
</body>
</html>