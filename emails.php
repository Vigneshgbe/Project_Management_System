<?php
require_once 'config.php';
require_once 'includes/layout.php';
require_once 'includes/mailer.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

// ── POST HANDLERS ──
ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── SEND EMAIL ──
    if ($action === 'send_email') {
        $to_raw   = trim($_POST['to']      ?? '');
        $cc_raw   = trim($_POST['cc']      ?? '');
        $subject  = trim($_POST['subject'] ?? '');
        $html     = $_POST['body']         ?? '';
        $cid      = (int)($_POST['contact_id']  ?? 0) ?: null;
        $lid      = (int)($_POST['lead_id']     ?? 0) ?: null;
        $inv_id   = (int)($_POST['invoice_id']  ?? 0) ?: null;
        $pid      = (int)($_POST['project_id']  ?? 0) ?: null;

        if (!$to_raw || !$subject) { flash('To and Subject required.','error'); ob_end_clean(); header('Location: emails.php'); exit; }

        $to  = array_filter(array_map('trim', explode(',', $to_raw)));
        $cc  = $cc_raw ? array_filter(array_map('trim', explode(',', $cc_raw))) : [];

        $smtp = $db->query("SELECT from_email,from_name FROM email_settings WHERE is_default=1 AND is_active=1 LIMIT 1")->fetch_assoc();

        try {
            $result = sendAndLog([
                'to'          => $to,
                'cc'          => $cc,
                'subject'     => $subject,
                'html'        => $html,
                'text'        => strip_tags($html),
                'from_email'  => $smtp['from_email'] ?? 'noreply@thepadak.com',
                'from_name'   => $smtp['from_name']  ?? 'Padak CRM',
                'sent_by'     => $uid,
                'contact_id'  => $cid,
                'lead_id'     => $lid,
                'invoice_id'  => $inv_id,
                'project_id'  => $pid,
            ], $db);
            if ($result['ok']) {
                flash('Email sent successfully.', 'success');
            } else {
                $short = strlen($result['error']) > 150 ? substr($result['error'],0,150).'...' : $result['error'];
                flash('Email queued (send error: '.$short.')', 'error');
            }
        } catch (\Throwable $e) {
            flash('Critical error: '.$e->getMessage(), 'error');
        }
        ob_end_clean(); header('Location: emails.php?tab=sent'); exit;
    }

    // ── SAVE TEMPLATE ──
    if ($action === 'save_template') {
        $tid     = (int)($_POST['tmpl_id'] ?? 0);
        $name    = trim($_POST['name']     ?? '');
        $cat     = trim($_POST['category'] ?? 'General');
        $subject = trim($_POST['subject']  ?? '');
        $body    = $_POST['body_html']     ?? '';
        if (!$name || !$subject) { flash('Name and subject required.','error'); ob_end_clean(); header('Location: emails.php?tab=templates'); exit; }
        if ($tid) {
            $stmt = $db->prepare("UPDATE email_templates SET name=?,category=?,subject=?,body_html=?,updated_at=NOW() WHERE id=? AND is_system=0");
            $stmt->bind_param("ssssi",$name,$cat,$subject,$body,$tid);
        } else {
            $stmt = $db->prepare("INSERT INTO email_templates (name,category,subject,body_html,created_by) VALUES (?,?,?,?,?)");
            $stmt->bind_param("ssssi",$name,$cat,$subject,$body,$uid);
        }
        $stmt->execute();
        flash('Template saved.','success');
        ob_end_clean(); header('Location: emails.php?tab=templates'); exit;
    }

    // ── DELETE TEMPLATE ──
    if ($action === 'delete_template' && isManager()) {
        $tid = (int)$_POST['tmpl_id'];
        $db->query("DELETE FROM email_templates WHERE id=$tid AND is_system=0");
        flash('Template deleted.','success');
        ob_end_clean(); header('Location: emails.php?tab=templates'); exit;
    }

    // ── SAVE SMTP SETTINGS ──
    if ($action === 'save_smtp' && isAdmin()) {
        $sid  = (int)($_POST['smtp_id'] ?? 0);
        $n    = trim($_POST['name']       ?? '');
        $fn   = trim($_POST['from_name']  ?? '');
        $fe   = trim($_POST['from_email'] ?? '');
        $host = trim($_POST['host']       ?? '');
        $port = (int)($_POST['port']      ?? 587);
        $enc  = $_POST['encryption']      ?? 'tls';
        $user = trim($_POST['username']   ?? '');
        $pass = trim($_POST['password']   ?? '');
        $def  = isset($_POST['is_default']) ? 1 : 0;
        if (!$n || !$fe) { flash('Name and From Email required.','error'); ob_end_clean(); header('Location: emails.php?tab=settings'); exit; }
        $imap_host = trim($_POST['imap_host'] ?? '');
        $imap_port = (int)($_POST['imap_port'] ?? 993);
        $imap_pass = trim($_POST['imap_password'] ?? '');
        if ($def) $db->query("UPDATE email_settings SET is_default=0");
        if ($sid) {
            $q = "UPDATE email_settings SET name=?,from_name=?,from_email=?,host=?,port=?,encryption=?,username=?,is_default=?,imap_host=?,imap_port=? WHERE id=?";
            $stmt = $db->prepare($q);
            $stmt->bind_param("ssssissisii",$n,$fn,$fe,$host,$port,$enc,$user,$def,$imap_host,$imap_port,$sid);
            if ($pass)      { $db->query("UPDATE email_settings SET password='".$db->real_escape_string($pass)."' WHERE id=$sid"); }
            if ($imap_pass) { $db->query("UPDATE email_settings SET imap_password='".$db->real_escape_string($imap_pass)."' WHERE id=$sid"); }
        } else {
            $stmt = $db->prepare("INSERT INTO email_settings (name,from_name,from_email,host,port,encryption,username,password,is_default,imap_host,imap_port,imap_password) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssisssisii",$n,$fn,$fe,$host,$port,$enc,$user,$pass,$def,$imap_host,$imap_port,$imap_pass);
        }
        $stmt->execute();
        flash('SMTP settings saved.','success');
        ob_end_clean(); header('Location: emails.php?tab=settings'); exit;
    }

    // ── DELETE SMTP ──
    if ($action === 'delete_smtp' && isAdmin()) {
        $db->query("DELETE FROM email_settings WHERE id=".(int)$_POST['smtp_id']);
        flash('Account deleted.','success');
        ob_end_clean(); header('Location: emails.php?tab=settings'); exit;
    }

    // ── TEST SMTP ──
    if ($action === 'test_smtp' && isAdmin()) {
        $test_to  = trim($_POST['test_to'] ?? $user['email']);
        $smtp_row = $db->query("SELECT * FROM email_settings WHERE id=".(int)$_POST['smtp_id'])->fetch_assoc();
        if ($smtp_row) {
            try {
                $res = smtpSend($smtp_row, [$test_to], [], [], 'Padak CRM SMTP Test',
                    '<div style="font-family:Arial,sans-serif;padding:20px"><h3 style="color:#f97316">✅ SMTP Working!</h3><p>This test confirms your SMTP is configured correctly in Padak CRM.</p></div>',
                    'SMTP test from Padak CRM.',
                    '<test.'.time().'@padak.local>');
                flash($res['ok'] ? '✅ Test email sent to '.$test_to : '❌ Test failed: '.$res['error'],
                      $res['ok'] ? 'success' : 'error');
            } catch (\Throwable $e) {
                flash('❌ SMTP error: '.$e->getMessage(), 'error');
            }
        } else {
            flash('SMTP account not found.', 'error');
        }
        ob_end_clean(); header('Location: emails.php?tab=settings'); exit;
    }

    // ── FETCH INBOX ──
    if ($action === 'fetch_inbox') {
        require_once 'includes/mailer.php';
        $result = fetchInboxEmails($db, 50);
        flash($result['ok'] ? '📥 Fetched '.$result['fetched'].' new email(s).' : '❌ IMAP Error: '.$result['error'],
              $result['ok'] ? 'success' : 'error');
        ob_end_clean(); header('Location: emails.php?tab=inbox'); exit;
    }

    // ── DELETE LOG ENTRY ──
    if ($action === 'delete_log') {
        $lid = (int)($_POST['log_id'] ?? 0);
        $db->query("DELETE FROM email_log WHERE id=$lid");
        flash('Email log entry deleted.','success');
        ob_end_clean(); header('Location: emails.php?tab=sent'); exit;
    }

    // ── RETRY FAILED EMAIL ──
    if ($action === 'retry_email') {
        $lid  = (int)($_POST['log_id'] ?? 0);
        $orig = $db->query("SELECT * FROM email_log WHERE id=$lid")->fetch_assoc();
        if ($orig) {
            $to_arr = json_decode($orig['to_email'] ?? '[]', true) ?: [];
            try {
                $result = sendAndLog([
                    'to'          => $to_arr,
                    'cc'          => json_decode($orig['cc_email']  ?? '[]', true) ?: [],
                    'subject'     => $orig['subject'],
                    'html'        => $orig['body_html'],
                    'text'        => $orig['body_text'] ?? strip_tags($orig['body_html']),
                    'from_email'  => $orig['from_email'],
                    'from_name'   => $orig['from_name'],
                    'sent_by'     => $uid,
                    'contact_id'  => $orig['contact_id'],
                    'project_id'  => $orig['project_id'],
                    'invoice_id'  => $orig['invoice_id'],
                ], $db);
                flash($result['ok'] ? '✅ Email resent successfully.' : '❌ Retry failed: '.substr($result['error'],0,120),
                      $result['ok'] ? 'success' : 'error');
            } catch (\Throwable $e) {
                flash('Retry error: '.$e->getMessage(), 'error');
            }
        }
        ob_end_clean(); header('Location: emails.php?tab=sent'); exit;
    }

    // ── MARK NOTIFICATION READ ──
    if ($action === 'mark_read') {
        $nid = (int)$_POST['nid'];
        $db->query("UPDATE notifications SET is_read=1 WHERE id=$nid AND user_id=$uid");
        ob_end_clean(); header('Location: emails.php?tab=notifications'); exit;
    }
    if ($action === 'mark_all_read') {
        $db->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid");
        ob_end_clean(); header('Location: emails.php?tab=notifications'); exit;
    }
}
ob_end_clean();

// ── LOAD DATA ──
$tab    = $_GET['tab']  ?? 'compose';
$search = trim($_GET['q'] ?? '');
$view_id= (int)($_GET['view'] ?? 0);

// Email log
$log_where = "1=1";
if ($search) $log_where .= " AND (el.subject LIKE '%".$db->real_escape_string($search)."%' OR el.to_email LIKE '%".$db->real_escape_string($search)."%' OR el.from_email LIKE '%".$db->real_escape_string($search)."%')";
if ($tab === 'sent') $log_where .= " AND el.direction='out' AND el.sent_by IS NOT NULL";

$email_log = $db->query("
    SELECT el.*, u.name AS sender_name,
           c.name AS contact_name
    FROM email_log el
    LEFT JOIN users u ON u.id=el.sent_by
    LEFT JOIN contacts c ON c.id=el.contact_id
    WHERE $log_where
    ORDER BY el.created_at DESC LIMIT 100
")->fetch_all(MYSQLI_ASSOC);

// Single email view
$view_email = null;
if ($view_id) {
    $view_email = $db->query("SELECT el.*,u.name AS sender_name FROM email_log el LEFT JOIN users u ON u.id=el.sent_by WHERE el.id=$view_id")->fetch_assoc();
}

// Templates
$templates = $db->query("SELECT * FROM email_templates ORDER BY category,name")->fetch_all(MYSQLI_ASSOC);

// SMTP accounts
$smtp_accounts = $db->query("SELECT * FROM email_settings ORDER BY is_default DESC,id")->fetch_all(MYSQLI_ASSOC);

// Notifications for current user
$notifs = $db->query("
    SELECT * FROM notifications WHERE user_id=$uid
    ORDER BY created_at DESC LIMIT 80
")->fetch_all(MYSQLI_ASSOC);
$unread_notif = (int)$db->query("SELECT COUNT(*) AS c FROM notifications WHERE user_id=$uid AND is_read=0")->fetch_assoc()['c'];

// Contacts + leads + projects + invoices for compose dropdowns
$contacts = $db->query("SELECT id,name,email,company FROM contacts WHERE email IS NOT NULL AND email!='' ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$leads    = $db->query("SELECT id,name,email,company FROM leads WHERE email IS NOT NULL AND email!='' ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$projects = $db->query("SELECT id,title FROM projects WHERE status NOT IN('cancelled') ORDER BY title")->fetch_all(MYSQLI_ASSOC);
$compose_invoices = $db->query("SELECT id,invoice_no,title FROM invoices WHERE status NOT IN('cancelled','paid') ORDER BY created_at DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);

// Edit template
$edit_tmpl = null;
if (isset($_GET['edit_tmpl'])) {
    $edit_tmpl = $db->query("SELECT * FROM email_templates WHERE id=".(int)$_GET['edit_tmpl']." AND is_system=0")->fetch_assoc();
}
// Pre-fill compose from ?to= or ?contact=
$compose_to   = trim($_GET['to']   ?? '');
$compose_subj = trim($_GET['subj'] ?? '');
$tmpl_id_load = (int)($_GET['tmpl'] ?? 0);
$tmpl_prefill = $tmpl_id_load ? $db->query("SELECT * FROM email_templates WHERE id=$tmpl_id_load")->fetch_assoc() : null;

// Email stats
$stats = $db->query("
    SELECT
        COUNT(*) AS total,
        SUM(status='sent') AS sent,
        SUM(status='failed') AS failed,
        SUM(opened_count>0) AS opened
    FROM email_log WHERE direction='out'
")->fetch_assoc();

renderLayout('Emails', 'emails');
?>

<style>
/* ── EMAIL HUB ── */
.em-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:20px;overflow-x:auto}
.em-tab{padding:10px 18px;font-size:13px;font-weight:600;color:var(--text3);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s;text-decoration:none;white-space:nowrap;display:flex;align-items:center;gap:5px}
.em-tab:hover,.em-tab.active{color:var(--orange);border-bottom-color:var(--orange)}
.em-badge{background:var(--red);color:#fff;font-size:9px;font-weight:800;padding:1px 5px;border-radius:99px}

/* Stats */
.em-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px}
.em-stat{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:14px 16px;display:flex;align-items:center;gap:12px}
.em-stat-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.em-stat-val{font-size:18px;font-weight:800;font-family:var(--font-display);color:var(--text)}
.em-stat-lbl{font-size:11px;color:var(--text3);text-transform:uppercase;letter-spacing:.04em}

/* Log rows */
.em-row{display:flex;align-items:center;gap:12px;padding:11px 14px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:6px;cursor:pointer;transition:border-color .12s}
.em-row:hover{border-color:var(--border2)}
.em-row.unread{border-left:3px solid var(--orange)}
.em-from{font-size:13px;font-weight:700;color:var(--text);min-width:140px;flex-shrink:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.em-subj{flex:1;min-width:0}
.em-subj-text{font-size:13px;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.em-subj-preview{font-size:11.5px;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.em-date{font-size:11.5px;color:var(--text3);flex-shrink:0;min-width:65px;text-align:right}

/* Template cards */
.tmpl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px}
.tmpl-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px;display:flex;flex-direction:column;gap:10px;transition:border-color .15s}
.tmpl-card:hover{border-color:var(--border2)}
.tmpl-cat{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding:2px 7px;border-radius:99px}

/* Notification list */
.notif-row{display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border-radius:var(--radius-sm);margin-bottom:5px;transition:background .12s;position:relative}
.notif-row.unread{background:var(--orange-bg);border-left:3px solid var(--orange)}
.notif-row.read{background:var(--bg2);border:1px solid var(--border)}
.notif-icon{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.notif-dot{position:absolute;top:14px;right:14px;width:7px;height:7px;background:var(--orange);border-radius:50%}

/* Compose layout */
.compose-grid{display:grid;grid-template-columns:1fr 280px;gap:18px;align-items:start}

/* SMTP card */
.smtp-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px;margin-bottom:12px}

/* Email detail view */
.em-detail-header{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;margin-bottom:14px}
.em-detail-body{background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;overflow:auto;min-height:200px}

@media(max-width:900px){.compose-grid{grid-template-columns:1fr}.em-stats{grid-template-columns:1fr 1fr}.em-from{min-width:100px}}
@media(max-width:500px){.em-stats{grid-template-columns:1fr 1fr}}
</style>

<!-- TABS -->
<div class="em-tabs">
  <?php
  $tabs = [
    ['compose',     '✉',  'Compose'],
    ['sent',        '📤', 'Sent Log'],
    ['inbox',       '📥', 'Inbox'],
    ['templates',   '📋', 'Templates'],
    ['notifications','🔔','Alerts', $unread_notif],
    ['settings',    '⚙',  'SMTP Settings'],
  ];
  foreach ($tabs as $t):
    $active = $tab === $t[0] ? 'active' : '';
  ?>
  <a href="emails.php?tab=<?= $t[0] ?>" class="em-tab <?= $active ?>">
    <?= $t[1] ?> <?= $t[2] ?>
    <?php if (!empty($t[3])): ?><span class="em-badge"><?= $t[3] ?></span><?php endif; ?>
  </a>
  <?php endforeach; ?>
</div>

<?php // ══════════════ COMPOSE TAB ══════════════
if ($tab === 'compose'): ?>

<div class="compose-grid">
  <div>
    <div class="card">
      <div class="card-header"><div class="card-title">✉ Compose Email</div></div>
      <form method="POST" id="compose-form">
        <input type="hidden" name="action" value="send_email">
        <input type="hidden" name="contact_id" id="hidden-contact-id">
        <input type="hidden" name="lead_id"    id="hidden-lead-id">
        <input type="hidden" name="project_id" id="hidden-project-id">

        <div class="form-group">
          <label class="form-label">To *</label>
          <input type="text" name="to" id="compose-to" class="form-control" required
            placeholder="email@example.com, another@example.com"
            value="<?= h($compose_to) ?>" autocomplete="off">
          <div id="contact-suggest" style="background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);display:none;max-height:180px;overflow-y:auto;z-index:100;position:relative"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Cc</label>
          <input type="text" name="cc" class="form-control" placeholder="cc@example.com">
        </div>
        <div class="form-group">
          <label class="form-label">Subject *</label>
          <input type="text" name="subject" id="compose-subject" class="form-control" required
            value="<?= h($compose_subj ?: ($tmpl_prefill['subject'] ?? '')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Link to</label>
          <div class="form-row">
            <select name="project_id" class="form-control" id="link-project">
              <option value="">— Project —</option>
              <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>"><?= h($p['title']) ?></option><?php endforeach; ?>
            </select>
            <select name="invoice_id" class="form-control">
              <option value="">— Invoice —</option>
              <?php foreach ($compose_invoices as $ci): ?>
              <option value="<?= $ci['id'] ?>"><?= h($ci['invoice_no']) ?> — <?= h(mb_substr($ci['title'],0,30)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Body</label>
          <textarea id="compose-tinymce" name="body" style="min-height:320px"><?= h($tmpl_prefill['body_html'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:6px">
          <button type="button" class="btn btn-ghost" onclick="saveDraftEmail()">💾 Save Draft</button>
          <button type="submit" class="btn btn-primary">Send Email ↑</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Sidebar: quick templates + contacts -->
  <div>
    <div class="card" style="margin-bottom:14px">
      <div class="card-title" style="margin-bottom:12px">Quick Templates</div>
      <?php foreach ($templates as $t): ?>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border)">
        <div>
          <div style="font-size:13px;font-weight:600;color:var(--text)"><?= h($t['name']) ?></div>
          <div style="font-size:11px;color:var(--text3)"><?= h($t['category']) ?></div>
        </div>
        <button class="btn btn-ghost btn-sm js-load-tmpl" data-tmpl='<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>'>Use</button>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="card">
      <div class="card-title" style="margin-bottom:10px">Quick Address</div>
      <input type="text" id="contact-quick-search" class="form-control" placeholder="Search contacts…" oninput="filterContacts(this.value)" style="margin-bottom:8px">
      <div id="contact-quick-list" style="max-height:220px;overflow-y:auto">
        <?php foreach (array_slice($contacts,0,20) as $c): ?>
        <div class="contact-quick-item" data-email="<?= h($c['email']) ?>" data-name="<?= h($c['name']) ?>" data-id="<?= $c['id'] ?>"
             style="padding:7px 8px;cursor:pointer;border-radius:5px;font-size:12.5px;transition:background .1s"
             onmouseenter="this.style.background='var(--bg3)'" onmouseleave="this.style.background=''"
             onclick="setComposeTo('<?= h(addslashes($c['name'])) ?>','<?= h(addslashes($c['email'])) ?>',<?= $c['id'] ?>,'contact')">
          <div style="font-weight:600"><?= h($c['name']) ?></div>
          <div style="color:var(--text3)"><?= h($c['email']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js"></script>
<script>
var isDark = document.documentElement.getAttribute('data-theme') !== 'light';
tinymce.init({
  selector: '#compose-tinymce',
  height: 340,
  menubar: false,
  plugins: ['lists','link','image','table','code'],
  toolbar: 'undo redo | bold italic underline | forecolor | alignleft aligncenter | bullist numlist | link image | code',
  skin: isDark ? 'oxide-dark' : 'oxide',
  content_css: isDark ? 'dark' : 'default',
  content_style: 'body{font-family:Arial,sans-serif;font-size:13pt;line-height:1.6;padding:8px}',
  promotion: false, branding: false,
});

function loadTemplate(t) {
  document.getElementById('compose-subject').value = t.subject || '';
  if (tinymce.get('compose-tinymce')) {
    tinymce.get('compose-tinymce').setContent(t.body_html || '');
  }
}

function setComposeTo(name, email, id, type) {
  document.getElementById('compose-to').value = name + ' <' + email + '>';
  if (type === 'contact') document.getElementById('hidden-contact-id').value = id;
  if (type === 'lead') document.getElementById('hidden-lead-id').value = id;
}

function saveDraftEmail() {
  if (tinymce.get('compose-tinymce')) {
    document.querySelector('[name="body"]').value = tinymce.get('compose-tinymce').getContent();
  }
  toast('Draft saved (feature: save to log as draft)', 'info');
}

function filterContacts(q) {
  q = q.toLowerCase();
  document.querySelectorAll('.contact-quick-item').forEach(function(el) {
    var text = el.dataset.name.toLowerCase() + ' ' + el.dataset.email.toLowerCase();
    el.style.display = text.includes(q) ? '' : 'none';
  });
}

document.getElementById('compose-form').addEventListener('submit', function() {
  if (tinymce.get('compose-tinymce')) {
    document.querySelector('[name="body"]').value = tinymce.get('compose-tinymce').getContent();
  }
});
</script>

<?php // ══════════════ SENT LOG TAB ══════════════
elseif ($tab === 'sent'):
  if ($view_email): ?>

<div style="margin-bottom:14px;display:flex;align-items:center;justify-content:space-between">
  <a href="emails.php?tab=sent" style="color:var(--text3);font-size:13px">← Back to Log</a>
  <div style="display:flex;gap:8px">
    <?php if ($view_email['opened_count'] > 0): ?><span style="font-size:12px;color:var(--green);font-weight:600">👁 Opened <?= $view_email['opened_count'] ?>
    <span class="badge" style="background:<?= $view_email['status']==='sent'?'rgba(16,185,129,.15)':'rgba(239,68,68,.15)' ?>;color:<?= $view_email['status']==='sent'?'var(--green)':'var(--red)' ?>"><?= ucfirst($view_email['status']) ?></span>
  </div>
</div>

<div class="em-detail-header">
  <div style="font-size:18px;font-weight:700;margin-bottom:12px"><?= h($view_email['subject']) ?></div>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
    <?php foreach ([
      ['From', $view_email['from_name'] ? h($view_email['from_name']).' &lt;'.h($view_email['from_email']).'&gt;' : h($view_email['from_email'])],
      ['To',   h(implode(', ', json_decode($view_email['to_email']??'[]',true)))],
      ['Sent', $view_email['sent_at'] ? date('M j, Y g:ia',strtotime($view_email['sent_at'])) : '—'],
    ] as [$l,$v]): ?>
    <div><div style="font-size:10.5px;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px"><?= $l ?></div><div style="font-size:12.5px;color:var(--text2)"><?= $v ?></div></div>
    <?php endforeach; ?>
  </div>
</div>
<div class="em-detail-body"><?= $view_email['body_html'] ?: nl2br(h($view_email['body_text']??'')) ?></div>

  <?php else: // log list ?>

<!-- Stats row -->
<div class="em-stats">
  <?php foreach ([
    ['📤','Sent',   (int)($stats['sent']??0),   'rgba(16,185,129,.12)'],
    ['❌','Failed',  (int)($stats['failed']??0), 'rgba(239,68,68,.12)'],
    ['👁','Opened',  (int)($stats['opened']??0), 'rgba(99,102,241,.12)'],
    ['📬','Total',   (int)($stats['total']??0),  'rgba(249,115,22,.12)'],
  ] as [$ic,$lb,$vl,$bg]): ?>
  <div class="em-stat">
    <div class="em-stat-icon" style="background:<?= $bg ?>"><?= $ic ?></div>
    <div><div class="em-stat-val"><?= $vl ?></div><div class="em-stat-lbl"><?= $lb ?></div></div>
  </div>
  <?php endforeach; ?>
</div>

<div style="display:flex;gap:8px;margin-bottom:14px">
  <form method="GET" style="flex:1;display:flex;gap:8px">
    <input type="hidden" name="tab" value="sent">
    <div class="search-box" style="flex:1;max-width:340px">
      <span style="color:var(--text3)">🔍</span>
      <input type="text" name="q" placeholder="Search emails…" value="<?= h($search) ?>">
    </div>
  </form>
</div>

<?php if (!$email_log): ?>
<div class="card"><div class="empty-state"><div class="icon">📤</div><p>No emails sent yet.</p></div></div>
<?php else: ?>
<?php foreach ($email_log as $em):
  $to_list = json_decode($em['to_email']??'[]',true);
  $to_str  = implode(', ', array_map(function($a){ return preg_match('/<([^>]+)>/',$a,$m)?$m[1]:$a; }, (array)$to_list));
  $sc      = $em['status']==='sent' ? 'var(--green)' : ($em['status']==='failed'?'var(--red)':'var(--text3)');
?>
<div class="em-row" style="cursor:default">
  <div class="em-from" style="cursor:pointer" onclick="location.href='emails.php?tab=sent&view=<?= $em['id'] ?>'"><?= h($em['sender_name'] ?? 'System') ?></div>
  <div class="em-subj" style="cursor:pointer" onclick="location.href='emails.php?tab=sent&view=<?= $em['id'] ?>'">
    <div class="em-subj-text"><?= h($em['subject']) ?></div>
    <div class="em-subj-preview">To: <?= h(mb_substr($to_str,0,55)) ?><?= $em['contact_name']?' · '.h($em['contact_name']):'' ?></div>
  </div>
  <?php if ($em['opened_count'] > 0): ?><span title="Opened <?= $em['opened_count'] ?>x" style="font-size:12px;color:var(--green);flex-shrink:0">👁</span><?php endif; ?>
  <span style="font-size:11px;font-weight:700;color:<?= $sc ?>;flex-shrink:0"><?= ucfirst($em['status']) ?></span>
  <div class="em-date"><?= date('M j, g:ia', strtotime($em['created_at'])) ?></div>
  <div style="display:flex;gap:4px;flex-shrink:0" onclick="event.stopPropagation()">
    <?php if ($em['status'] === 'failed'): ?>
    <form method="POST" style="display:inline">
      <input type="hidden" name="action"  value="retry_email">
      <input type="hidden" name="log_id"  value="<?= $em['id'] ?>">
      <button class="btn btn-ghost btn-sm btn-icon" title="Retry sending" style="color:var(--orange)">↺</button>
    </form>
    <?php endif; ?>
    <form method="POST" onsubmit="return confirm('Delete this log entry?')" style="display:inline">
      <input type="hidden" name="action"  value="delete_log">
      <input type="hidden" name="log_id"  value="<?= $em['id'] ?>">
      <button class="btn btn-danger btn-sm btn-icon" title="Delete">🗑</button>
    </form>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
  <?php endif; // view vs list ?>


<?php // ══════════════ TEMPLATES TAB ══════════════
elseif ($tab === 'templates'): ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
  <div style="font-size:13px;color:var(--text3)"><?= count($templates) ?> templates available</div>
  <button class="btn btn-primary" onclick="openModal('modal-tmpl')">＋ New Template</button>
</div>

<?php
$grouped = [];
foreach ($templates as $t) $grouped[$t['category']][] = $t;
foreach ($grouped as $cat => $tmpl_list):
?>
<div style="margin-bottom:20px">
  <div style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px"><?= h($cat) ?></div>
  <div class="tmpl-grid">
    <?php foreach ($tmpl_list as $t): ?>
    <div class="tmpl-card">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px">
        <div style="font-size:13.5px;font-weight:700;color:var(--text)"><?= h($t['name']) ?></div>
        <?php if ($t['is_system']): ?><span style="font-size:10px;color:var(--text3);flex-shrink:0">SYSTEM</span><?php endif; ?>
      </div>
      <div style="font-size:12px;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($t['subject']) ?></div>
      <div style="display:flex;gap:6px;margin-top:auto">
        <a href="emails.php?tab=compose&tmpl=<?= $t['id'] ?>" class="btn btn-ghost btn-sm">Use ↗</a>
        <?php if (!$t['is_system']): ?>
        <button class="btn btn-ghost btn-sm js-edit-tmpl" data-tmpl='<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>'>✎ Edit</button>
        <form method="POST" onsubmit="return confirm('Delete template?')" style="display:inline">
          <input type="hidden" name="action" value="delete_template">
          <input type="hidden" name="tmpl_id" value="<?= $t['id'] ?>">
          <button class="btn btn-danger btn-sm btn-icon">🗑</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>

<!-- Template Modal -->
<div class="modal-overlay" id="modal-tmpl">
  <div class="modal" style="max-width:700px;width:95vw">
    <div class="modal-header"><div class="modal-title" id="tmpl-modal-title">New Template</div><button class="modal-close" onclick="closeModal('modal-tmpl')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="save_template">
      <input type="hidden" name="tmpl_id" id="tmpl-edit-id" value="0">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Template Name *</label><input type="text" name="name" id="tmpl-name" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Category</label>
            <input type="text" name="category" id="tmpl-cat" class="form-control" value="General" list="tmpl-cats">
            <datalist id="tmpl-cats"><option>General</option><option>Sales</option><option>Billing</option><option>Projects</option><option>Notifications</option><option>HR</option></datalist>
          </div>
        </div>
        <div class="form-group"><label class="form-label">Subject *</label><input type="text" name="subject" id="tmpl-subject" class="form-control" required placeholder="Use {{name}}, {{company}}, {{project}}, etc."></div>
        <div class="form-group">
          <label class="form-label">Body HTML</label>
          <textarea name="body_html" id="tmpl-body" class="form-control" style="min-height:200px;font-family:monospace;font-size:12px"></textarea>
          <div style="font-size:11px;color:var(--text3);margin-top:4px">Variables: <code>{{name}}</code> <code>{{company}}</code> <code>{{project}}</code> <code>{{invoice_no}}</code> <code>{{amount}}</code> <code>{{due_date}}</code> <code>{{task}}</code> <code>{{link}}</code></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('modal-tmpl')">Cancel</button><button type="submit" class="btn btn-primary">Save Template</button></div>
    </form>
  </div>
</div>
<script>
function editTemplate(t) {
  document.getElementById('tmpl-modal-title').textContent = 'Edit Template';
  document.getElementById('tmpl-edit-id').value = t.id;
  document.getElementById('tmpl-name').value    = t.name    || '';
  document.getElementById('tmpl-cat').value     = t.category || '';
  document.getElementById('tmpl-subject').value = t.subject  || '';
  document.getElementById('tmpl-body').value    = t.body_html || '';
  openModal('modal-tmpl');
}
// Event delegation for data-tmpl and data-tmpl edit buttons
document.addEventListener('click', function(e) {
  var btn = e.target.closest('.js-load-tmpl');
  if (btn) {
    try { loadTemplate(JSON.parse(btn.dataset.tmpl)); } catch(ex) { console.error('Template parse error', ex); }
    return;
  }
  btn = e.target.closest('.js-edit-tmpl');
  if (btn) {
    try { editTemplate(JSON.parse(btn.dataset.tmpl)); } catch(ex) { console.error('Template parse error', ex); }
  }
});
</script>

<?php // ══════════════ INBOX TAB ══════════════
elseif ($tab === 'inbox'):
    $inbox_emails = $db->query("
        SELECT * FROM email_log
        WHERE direction='in'
        ORDER BY sent_at DESC
        LIMIT 100
    ")->fetch_all(MYSQLI_ASSOC);
    $view_inbox = $view_id ? $db->query("SELECT * FROM email_log WHERE id=$view_id AND direction='in'")->fetch_assoc() : null;
?>

<?php if ($view_inbox): ?>
<div style="margin-bottom:14px;display:flex;align-items:center;justify-content:space-between">
  <a href="emails.php?tab=inbox" style="color:var(--text3);font-size:13px">← Back to Inbox</a>
</div>
<div class="em-detail-header">
  <div style="font-size:18px;font-weight:700;margin-bottom:12px"><?= h($view_inbox['subject']) ?></div>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
    <?php foreach ([
      ['From', h($view_inbox['from_name'] ? $view_inbox['from_name'].' <'.$view_inbox['from_email'].'>' : $view_inbox['from_email'])],
      ['To',   h(implode(', ', json_decode($view_inbox['to_email']??'[]',true)))],
      ['Date', $view_inbox['sent_at'] ? date('M j, Y g:ia', strtotime($view_inbox['sent_at'])) : '—'],
    ] as [$l,$v]): ?>
    <div><div style="font-size:10.5px;color:var(--text3);text-transform:uppercase;margin-bottom:2px"><?= $l ?></div><div style="font-size:12.5px;color:var(--text2)"><?= $v ?></div></div>
    <?php endforeach; ?>
  </div>
</div>
<div class="em-detail-body"><?= $view_inbox['body_html'] ?: nl2br(h($view_inbox['body_text']??'')) ?></div>

<?php else: ?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
  <div style="font-size:13px;color:var(--text3)"><?= count($inbox_emails) ?> email<?= count($inbox_emails)!=1?'s':'' ?> in inbox</div>
  <form method="POST" style="display:inline">
    <input type="hidden" name="action" value="fetch_inbox">
    <button class="btn btn-primary">📥 Fetch New Emails</button>
  </form>
</div>

<?php if (!$inbox_emails): ?>
<div class="card">
  <div class="empty-state">
    <div class="icon">📥</div>
    <p>No emails in inbox yet.<br><small style="color:var(--text3)">Configure IMAP in SMTP Settings, then click Fetch New Emails.</small></p>
  </div>
</div>
<?php else: ?>
<?php foreach ($inbox_emails as $em): ?>
<div class="em-row" onclick="location.href='emails.php?tab=inbox&view=<?= $em['id'] ?>'" style="cursor:pointer">
  <div class="em-from"><?= h($em['from_name'] ?: $em['from_email']) ?></div>
  <div class="em-subj">
    <div class="em-subj-text"><?= h($em['subject']) ?></div>
    <div class="em-subj-preview"><?= h(mb_substr(strip_tags($em['body_text'] ?? $em['body_html'] ?? ''), 0, 80)) ?></div>
  </div>
  <div class="em-date"><?= $em['sent_at'] ? date('M j, g:ia', strtotime($em['sent_at'])) : '—' ?></div>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php endif; // view vs list ?>


<?php // ══════════════ NOTIFICATIONS TAB ══════════════
elseif ($tab === 'notifications'): ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
  <div style="font-size:13px;color:var(--text3)"><?= $unread_notif ?> unread notification<?= $unread_notif!=1?'s':'' ?></div>
  <?php if ($unread_notif): ?>
  <form method="POST" style="display:inline"><input type="hidden" name="action" value="mark_all_read"><button class="btn btn-ghost btn-sm">✓ Mark all read</button></form>
  <?php endif; ?>
</div>

<?php if (!$notifs): ?>
<div class="card"><div class="empty-state"><div class="icon">🔔</div><p>No notifications yet.</p></div></div>
<?php else: ?>
<?php
$type_icons = [
  'task_assigned'=>['🧑‍💼','rgba(99,102,241,.15)','#6366f1'],
  'task_due'     =>['⏰',   'rgba(239,68,68,.15)', '#ef4444'],
  'invoice_sent' =>['🧾',   'rgba(249,115,22,.15)','#f97316'],
  'lead_update'  =>['🎯',   'rgba(16,185,129,.15)','#10b981'],
  'mention'      =>['@',    'rgba(139,92,246,.15)', '#8b5cf6'],
  'info'         =>['ℹ',    'rgba(148,163,184,.15)','#94a3b8'],
];
foreach ($notifs as $n):
  [$ic,$ibg,$icolor] = $type_icons[$n['type']] ?? $type_icons['info'];
  $cls = $n['is_read'] ? 'read' : 'unread';
?>
<div class="notif-row <?= $cls ?>">
  <div class="notif-icon" style="background:<?= $ibg ?>;color:<?= $icolor ?>"><?= $ic ?></div>
  <div style="flex:1;min-width:0">
    <div style="font-size:13.5px;font-weight:<?= $n['is_read']?'500':'700' ?>;color:var(--text);margin-bottom:2px"><?= h($n['title']) ?></div>
    <?php if ($n['body']): ?><div style="font-size:12px;color:var(--text3)"><?= h($n['body']) ?></div><?php endif; ?>
    <div style="font-size:11px;color:var(--text3);margin-top:3px"><?= date('M j, Y g:ia',strtotime($n['created_at'])) ?></div>
  </div>
  <div style="display:flex;gap:6px;flex-shrink:0">
    <?php if ($n['link']): ?><a href="<?= h($n['link']) ?>" class="btn btn-ghost btn-sm">View</a><?php endif; ?>
    <?php if (!$n['is_read']): ?>
    <form method="POST" style="display:inline"><input type="hidden" name="action" value="mark_read"><input type="hidden" name="nid" value="<?= $n['id'] ?>"><button class="btn btn-ghost btn-sm">✓</button></form>
    <?php endif; ?>
  </div>
  <?php if (!$n['is_read']): ?><div class="notif-dot"></div><?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>


<?php // ══════════════ SETTINGS TAB ══════════════
elseif ($tab === 'settings' && isAdmin()): ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
  <div style="font-size:13px;color:var(--text3)">Configure SMTP accounts for outgoing email</div>
  <button class="btn btn-primary" onclick="openModal('modal-smtp')">＋ Add SMTP Account</button>
</div>

<?php if (!$smtp_accounts): ?>
<div class="card">
  <div class="empty-state">
    <div class="icon">⚙</div>
    <p>No SMTP configured. Emails will use PHP <code>mail()</code>.<br>Add SMTP for reliable delivery.</p>
  </div>
</div>
<?php else: ?>
<?php foreach ($smtp_accounts as $s): ?>
<div class="smtp-card">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px">
    <div style="flex:1">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
        <div style="font-size:14px;font-weight:700"><?= h($s['name']) ?></div>
        <?php if ($s['is_default']): ?><span class="badge badge-success" style="font-size:10px">Default</span><?php endif; ?>
        <span class="badge <?= $s['is_active']?'badge-success':'badge-error' ?>" style="font-size:10px"><?= $s['is_active']?'Active':'Inactive' ?></span>
      </div>
      <div style="font-size:12.5px;color:var(--text2)"><?= h($s['from_name']) ?> &lt;<?= h($s['from_email']) ?>&gt;</div>
      <div style="font-size:12px;color:var(--text3);margin-top:3px"><?= $s['host'] ? h($s['host']).':'.$s['port'].' ('.strtoupper($s['encryption']).')' : 'PHP mail() fallback' ?></div>
    </div>
    <div style="display:flex;gap:6px;flex-shrink:0">
      <button class="btn btn-ghost btn-sm js-edit-smtp" data-smtp='<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>'>✎</button>
      <!-- Test -->
      <button class="btn btn-ghost btn-sm" onclick="document.getElementById('test-smtp-id').value='<?= $s['id'] ?>';openModal('modal-test')">Test</button>
      <form method="POST" onsubmit="return confirm('Delete SMTP account?')" style="display:inline">
        <input type="hidden" name="action" value="delete_smtp"><input type="hidden" name="smtp_id" value="<?= $s['id'] ?>">
        <button class="btn btn-danger btn-sm btn-icon">🗑</button>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- Quick Gmail Setup -->
<div class="card" style="border-color:var(--orange);background:var(--orange-bg);margin-bottom:14px">
  <div style="font-size:13px;font-weight:700;color:var(--orange);margin-bottom:10px">⚡ Quick Gmail Setup</div>
  <div style="font-size:12.5px;color:var(--text2);margin-bottom:12px">Use a Gmail account with an <strong>App Password</strong> (not your regular password). <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color:var(--orange)">Generate App Password →</a></div>
  <button class="btn btn-primary btn-sm" onclick="prefillGmail()">🚀 Configure Gmail SMTP</button>
</div>

<!-- Info box -->
<div class="card" style="border-color:var(--blue);background:rgba(99,102,241,.05)">
  <div style="font-size:13px;font-weight:700;color:var(--blue);margin-bottom:8px">ℹ Automated Email Triggers</div>
  <div style="font-size:12.5px;color:var(--text2);line-height:1.8">
    <div>📋 <strong>Task Assigned</strong> — triggered automatically when a task is assigned in tasks.php</div>
    <div>⏰ <strong>Task Due Reminders</strong> — cron: <code style="background:var(--bg4);padding:1px 6px;border-radius:3px">php cron_reminders.php</code></div>
    <div>🧾 <strong>Invoice Sent</strong> — triggered when invoice status changed to "sent"</div>
    <div>🔔 <strong>Notifications</strong> — all triggers create in-app + email notifications</div>
  </div>
</div>

<!-- SMTP Modal -->
<div class="modal-overlay" id="modal-smtp">
  <div class="modal" style="max-width:520px">
    <div class="modal-header"><div class="modal-title" id="smtp-modal-title">Add SMTP Account</div><button class="modal-close" onclick="closeModal('modal-smtp')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="save_smtp">
      <input type="hidden" name="smtp_id" id="smtp-edit-id" value="0">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Account Name *</label><input type="text" name="name" id="smtp-name" class="form-control" required placeholder="e.g. Padak Main"></div>
          <div class="form-group"><label class="form-label">From Name *</label><input type="text" name="from_name" id="smtp-fname" class="form-control" required placeholder="Padak CRM"></div>
        </div>
        <div class="form-group"><label class="form-label">From Email *</label><input type="email" name="from_email" id="smtp-femail" class="form-control" required placeholder="noreply@thepadak.com"></div>
        <div style="background:var(--bg3);border-radius:var(--radius-sm);padding:12px;margin-bottom:14px">
          <div style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px">SMTP Server (leave blank to use PHP mail())</div>
          <div class="form-row">
            <div class="form-group" style="flex:2"><label class="form-label">Host</label><input type="text" name="host" id="smtp-host" class="form-control" placeholder="smtp.gmail.com"></div>
            <div class="form-group"><label class="form-label">Port</label><input type="number" name="port" id="smtp-port" class="form-control" value="587"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Encryption</label>
              <select name="encryption" id="smtp-enc" class="form-control"><option value="tls">TLS (587)</option><option value="ssl">SSL (465)</option><option value="none">None</option></select>
            </div>
            <div class="form-group"><label class="form-label">Username</label><input type="text" name="username" id="smtp-user" class="form-control" autocomplete="off"></div>
          </div>
          <div class="form-group"><label class="form-label">Password <span style="font-weight:400;color:var(--text3)">(leave blank to keep)</span></label><input type="password" name="password" id="smtp-pass" class="form-control" autocomplete="new-password"></div>
        </div>
        <div style="background:var(--bg3);border-radius:var(--radius-sm);padding:12px;margin-bottom:14px">
          <div style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px">IMAP (Inbox Receiving — optional)</div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">IMAP Host</label><input type="text" name="imap_host" id="smtp-imap-host" class="form-control" placeholder="imap.gmail.com"></div>
            <div class="form-group"><label class="form-label">IMAP Port</label><input type="number" name="imap_port" id="smtp-imap-port" class="form-control" value="993"></div>
          </div>
          <div class="form-group"><label class="form-label">IMAP Password <span style="font-weight:400;color:var(--text3)">(if different from SMTP)</span></label><input type="password" name="imap_password" id="smtp-imap-pass" class="form-control" autocomplete="new-password"></div>
        </div>
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
          <input type="checkbox" name="is_default" id="smtp-def" style="accent-color:var(--orange)"> Set as default account
        </label>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('modal-smtp')">Cancel</button><button type="submit" class="btn btn-primary">Save Account</button></div>
    </form>
  </div>
</div>

<!-- Test SMTP Modal -->
<div class="modal-overlay" id="modal-test">
  <div class="modal" style="max-width:400px">
    <div class="modal-header"><div class="modal-title">Test SMTP</div><button class="modal-close" onclick="closeModal('modal-test')">✕</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="test_smtp">
      <input type="hidden" name="smtp_id" id="test-smtp-id" value="">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Send test email to</label>
          <input type="email" name="test_to" class="form-control" value="<?= h($user['email']) ?>" required>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('modal-test')">Cancel</button><button type="submit" class="btn btn-primary">Send Test</button></div>
    </form>
  </div>
</div>
<script>
// Event delegation for SMTP edit buttons
document.addEventListener('click', function(e) {
  var btn = e.target.closest('.js-edit-smtp');
  if (btn) {
    try { editSmtp(JSON.parse(btn.dataset.smtp)); } catch(ex) { console.error('SMTP parse error', ex); }
  }
});
function prefillGmail() {
  document.getElementById('smtp-modal-title').textContent = 'Configure Gmail SMTP';
  document.getElementById('smtp-edit-id').value = 0;
  document.getElementById('smtp-name').value    = 'Gmail';
  document.getElementById('smtp-fname').value   = 'Padak CRM';
  document.getElementById('smtp-femail').value  = '';
  document.getElementById('smtp-host').value    = 'smtp.gmail.com';
  document.getElementById('smtp-port').value    = 465;
  document.getElementById('smtp-enc').value     = 'ssl';
  document.getElementById('smtp-user').value    = '';
  document.getElementById('smtp-pass').value    = '';
  document.getElementById('smtp-def').checked   = true;
  openModal('modal-smtp');
}
function editSmtp(s) {
  document.getElementById('smtp-modal-title').textContent = 'Edit SMTP Account';
  document.getElementById('smtp-edit-id').value = s.id;
  document.getElementById('smtp-name').value    = s.name;
  document.getElementById('smtp-fname').value   = s.from_name;
  document.getElementById('smtp-femail').value  = s.from_email;
  document.getElementById('smtp-host').value    = s.host || '';
  document.getElementById('smtp-port').value    = s.port || 587;
  document.getElementById('smtp-enc').value     = s.encryption || 'tls';
  document.getElementById('smtp-user').value    = s.username || '';
  document.getElementById('smtp-pass').value    = '';
  document.getElementById('smtp-def').checked   = s.is_default == 1;
  document.getElementById('smtp-imap-host').value = s.imap_host || '';
  document.getElementById('smtp-imap-port').value = s.imap_port || 993;
  document.getElementById('smtp-imap-pass').value = '';
  openModal('modal-smtp');
}
</script>

<?php endif; // tab routing ?>

<?php renderLayoutEnd(); ?>