<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
requireRole(['admin','manager']);
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── CREATE / UPDATE PORTAL ACCOUNT ──
    if ($action === 'save_portal') {
        $contact_id = (int)$_POST['contact_id'];
        $email      = trim($_POST['email'] ?? '');
        $pass       = trim($_POST['password'] ?? '');
        $pid        = (int)($_POST['portal_id'] ?? 0);
        $status     = $_POST['status'] ?? 'active';
        if (!$contact_id || !$email) { flash('Contact and email required.','error'); ob_end_clean(); header('Location: portal_admin.php'); exit; }
        if ($pid) {
            // Update
            if ($pass) {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE client_portal SET email=?,password=?,status=? WHERE id=?");
                $stmt->bind_param("sssi",$email,$hash,$status,$pid);
            } else {
                $stmt = $db->prepare("UPDATE client_portal SET email=?,status=? WHERE id=?");
                $stmt->bind_param("ssi",$email,$status,$pid);
            }
            $stmt->execute();
            flash('Portal account updated.','success');
        } else {
            if (!$pass) { flash('Password required for new account.','error'); ob_end_clean(); header('Location: portal_admin.php'); exit; }
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO client_portal (contact_id,email,password,status) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE email=VALUES(email),password=VALUES(password),status=VALUES(status)");
            $stmt->bind_param("isss",$contact_id,$email,$hash,$status);
            $stmt->execute();
            flash('Portal account created.','success');
        }
        ob_end_clean(); header('Location: portal_admin.php'); exit;
    }

    // ── DELETE PORTAL ACCOUNT ──
    if ($action === 'delete_portal' && isAdmin()) {
        $pid = (int)$_POST['portal_id'];
        $db->query("DELETE FROM client_portal WHERE id=$pid");
        flash('Portal account deleted.','success');
        ob_end_clean(); header('Location: portal_admin.php'); exit;
    }

    // ── REPLY TO CLIENT MESSAGE ──
    if ($action === 'reply_message') {
        $cid     = (int)$_POST['contact_id'];
        $proj_id = (int)($_POST['project_id'] ?? 0) ?: null;
        $subject = trim($_POST['subject'] ?? '');
        $body    = trim($_POST['body'] ?? '');
        if ($body && $cid) {
            $stmt = $db->prepare("INSERT INTO client_messages (contact_id,project_id,sender_type,sender_id,subject,body,is_read) VALUES (?,?,'staff',?,?,?,0)");
            $stmt->bind_param("iiiss",$cid,$proj_id,$uid,$subject,$body);
            $stmt->execute();
            flash('Reply sent.','success');
        }
        ob_end_clean(); header('Location: portal_admin.php?tab=messages&cid='.$cid); exit;
    }
}
ob_end_clean();

$tab     = $_GET['tab'] ?? 'accounts';
$msg_cid = (int)($_GET['cid'] ?? 0);

// All portal accounts
$accounts = $db->query("
    SELECT cp.*, c.name, c.company, c.email AS contact_email
    FROM client_portal cp
    JOIN contacts c ON c.id=cp.contact_id
    ORDER BY c.name
")->fetch_all(MYSQLI_ASSOC);

// Contacts without portal accounts
$no_portal = $db->query("
    SELECT id,name,company,email FROM contacts
    WHERE type='client' AND status='active'
    AND id NOT IN (SELECT contact_id FROM client_portal)
    ORDER BY name
")->fetch_all(MYSQLI_ASSOC);

$all_contacts = $db->query("SELECT id,name,company FROM contacts WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Messages grouped by contact
$msg_contacts = $db->query("
    SELECT cm.contact_id, c.name, c.company,
        COUNT(*) AS total,
        SUM(CASE WHEN cm.sender_type='client' AND cm.is_read=0 THEN 1 ELSE 0 END) AS unread,
        MAX(cm.created_at) AS last_at,
        (SELECT body FROM client_messages WHERE contact_id=cm.contact_id ORDER BY created_at DESC LIMIT 1) AS last_msg
    FROM client_messages cm
    JOIN contacts c ON c.id=cm.contact_id
    GROUP BY cm.contact_id, c.name, c.company
    ORDER BY last_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Single thread
$thread = []; $thread_contact = null;
if ($msg_cid) {
    $thread_contact = $db->query("SELECT id,name,company,email FROM contacts WHERE id=$msg_cid")->fetch_assoc();
    $thread = $db->query("
        SELECT cm.*, u.name AS staff_name
        FROM client_messages cm
        LEFT JOIN users u ON u.id=cm.sender_id AND cm.sender_type='staff'
        WHERE cm.contact_id=$msg_cid
        ORDER BY cm.created_at ASC
    ")->fetch_all(MYSQLI_ASSOC);
    // Mark client messages as read
    $db->query("UPDATE client_messages SET is_read=1 WHERE contact_id=$msg_cid AND sender_type='client'");
}

// Total unread across all
$total_unread = (int)$db->query("SELECT COUNT(*) AS c FROM client_messages WHERE sender_type='client' AND is_read=0")->fetch_assoc()['c'];

renderLayout('Client Portal', 'portal_admin');
?>

<style>
.pa-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:20px}
.pa-tab{padding:10px 20px;font-size:13px;font-weight:600;color:var(--text3);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s;text-decoration:none;display:flex;align-items:center;gap:6px}
.pa-tab:hover,.pa-tab.active{color:var(--orange);border-bottom-color:var(--orange)}
.pa-badge{background:var(--red);color:#fff;font-size:9px;font-weight:800;padding:1px 5px;border-radius:99px}

/* Accounts */
.acc-row{display:flex;align-items:center;gap:14px;padding:13px 16px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:8px}
.acc-avatar{width:36px;height:36px;border-radius:50%;background:var(--orange-bg);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:var(--orange);flex-shrink:0}

/* Messages */
.msg-layout{display:grid;grid-template-columns:260px 1fr;gap:16px;height:calc(100vh - var(--header-h) - 180px)}
.msg-contacts{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow-y:auto}
.msg-crow{padding:12px 14px;cursor:pointer;border-bottom:1px solid var(--border);transition:background .12s}
.msg-crow:hover{background:var(--bg3)}
.msg-crow.active{background:var(--orange-bg)}
.msg-thread{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);display:flex;flex-direction:column}
.msg-thread-body{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px}
.msg-thread-reply{padding:12px 14px;border-top:1px solid var(--border);flex-shrink:0}
.bubble{max-width:70%;padding:10px 13px;border-radius:12px;font-size:13px;line-height:1.55;word-break:break-word}
.bubble-client{background:var(--bg3);border:1px solid var(--border);align-self:flex-start;border-bottom-left-radius:3px}
.bubble-staff{background:var(--orange);color:#fff;align-self:flex-end;border-bottom-right-radius:3px}

@media(max-width:768px){.msg-layout{grid-template-columns:1fr;height:auto}}
</style>

<div class="pa-tabs">
  <a href="portal_admin.php?tab=accounts" class="pa-tab <?= $tab==='accounts'?'active':'' ?>">👤 Client Accounts</a>
  <a href="portal_admin.php?tab=messages" class="pa-tab <?= $tab==='messages'?'active':'' ?>">
    💬 Messages <?php if ($total_unread): ?><span class="pa-badge"><?= $total_unread ?></span><?php endif; ?>
  </a>
</div>

<?php if ($tab === 'accounts'): ?>

<!-- ACCOUNTS TAB -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
  <div style="font-size:13px;color:var(--text3)"><?= count($accounts) ?> active portal accounts</div>
  <button class="btn btn-primary" onclick="openModal('modal-new-account')">＋ Create Portal Account</button>
</div>

<?php if (!$accounts): ?>
<div class="card"><div class="empty-state"><div class="icon">👤</div><p>No portal accounts yet. Create one to give clients access.</p></div></div>
<?php else: ?>
<?php foreach ($accounts as $a): ?>
<div class="acc-row">
  <div class="acc-avatar"><?= strtoupper(substr($a['name'],0,1)) ?></div>
  <div style="flex:1;min-width:0">
    <div style="font-size:14px;font-weight:700"><?= h($a['name']) ?><?= $a['company']?' <span style="font-size:12px;color:var(--text3);">· '.h($a['company']).'</span>':'' ?></div>
    <div style="font-size:12px;color:var(--text3)"><?= h($a['email']) ?> · Last login: <?= $a['last_login'] ? date('M j, Y g:ia',strtotime($a['last_login'])) : 'Never' ?></div>
  </div>
  <span class="badge <?= $a['status']==='active'?'badge-success':'badge-error' ?>"><?= ucfirst($a['status']) ?></span>
  <div style="display:flex;gap:6px">
    <button class="btn btn-ghost btn-sm" onclick='editAccount(<?= json_encode($a) ?>)'>✎ Edit</button>
    <?php if (isAdmin()): ?>
    <form method="POST" onsubmit="return confirm('Delete this portal account?')" style="display:inline">
      <input type="hidden" name="action" value="delete_portal">
      <input type="hidden" name="portal_id" value="<?= $a['id'] ?>">
      <button class="btn btn-danger btn-sm btn-icon">🗑</button>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($no_portal): ?>
<div class="card" style="margin-top:16px">
  <div class="card-header"><div class="card-title" style="font-size:13px;color:var(--text3)">Clients without portal access (<?= count($no_portal) ?>)</div></div>
  <?php foreach ($no_portal as $c): ?>
  <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px">
    <div><?= h($c['name']) ?><?= $c['company']?' <span style="color:var(--text3);">· '.h($c['company']).'</span>':'' ?></div>
    <button class="btn btn-ghost btn-sm" onclick="quickCreate(<?= $c['id'] ?>,<?= json_encode($c['name']) ?>,<?= json_encode($c['email']??'') ?>)">＋ Grant Access</button>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- NEW / EDIT ACCOUNT MODAL -->
<div class="modal-overlay" id="modal-new-account">
  <div class="modal" style="max-width:480px">
    <div class="modal-header">
      <div class="modal-title" id="acc-modal-title">Create Portal Account</div>
      <button class="modal-close" onclick="closeModal('modal-new-account')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="save_portal">
      <input type="hidden" name="portal_id" id="edit-portal-id" value="0">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Client Contact *</label>
          <select name="contact_id" id="edit-contact-id" class="form-control">
            <option value="">— Select Contact —</option>
            <?php foreach ($all_contacts as $c): ?>
            <option value="<?= $c['id'] ?>"><?= h($c['name']) ?><?= $c['company']?' · '.h($c['company']):'' ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Login Email *</label>
          <input type="email" name="email" id="edit-email" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Password <span id="pass-hint" style="color:var(--text3);font-weight:400">(leave blank to keep existing)</span></label>
          <input type="password" name="password" id="edit-pass" class="form-control" placeholder="Min 8 characters" autocomplete="new-password">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" id="edit-status" class="form-control">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-new-account')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Account</button>
      </div>
    </form>
  </div>
</div>

<script>
function editAccount(a) {
    document.getElementById('acc-modal-title').textContent = 'Edit Portal Account';
    document.getElementById('edit-portal-id').value  = a.id;
    document.getElementById('edit-contact-id').value = a.contact_id;
    document.getElementById('edit-email').value       = a.email;
    document.getElementById('edit-pass').value        = '';
    document.getElementById('edit-status').value      = a.status;
    document.getElementById('pass-hint').style.display = '';
    openModal('modal-new-account');
}
function quickCreate(id, name, email) {
    document.getElementById('acc-modal-title').textContent = 'Grant Portal Access — ' + name;
    document.getElementById('edit-portal-id').value  = 0;
    document.getElementById('edit-contact-id').value = id;
    document.getElementById('edit-email').value       = email;
    document.getElementById('edit-pass').value        = '';
    document.getElementById('pass-hint').style.display = 'none';
    openModal('modal-new-account');
}
</script>

<?php else: // MESSAGES TAB ?>

<div class="msg-layout">
  <!-- Contact list -->
  <div class="msg-contacts">
    <div style="padding:12px 14px;border-bottom:1px solid var(--border);font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em">Conversations</div>
    <?php if (!$msg_contacts): ?>
    <div style="padding:24px;text-align:center;color:var(--text3);font-size:13px">No messages yet</div>
    <?php endif; ?>
    <?php foreach ($msg_contacts as $mc): ?>
    <a href="portal_admin.php?tab=messages&cid=<?= $mc['contact_id'] ?>" class="msg-crow <?= $msg_cid===$mc['contact_id']?'active':'' ?>">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div style="font-size:13px;font-weight:700;color:var(--text)"><?= h($mc['name']) ?></div>
        <?php if ($mc['unread']): ?><span style="background:var(--red);color:#fff;font-size:9px;font-weight:800;padding:1px 5px;border-radius:99px;flex-shrink:0"><?= $mc['unread'] ?></span><?php endif; ?>
      </div>
      <div style="font-size:11.5px;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px"><?= h(mb_substr(strip_tags($mc['last_msg']??''),0,48)) ?></div>
      <div style="font-size:10.5px;color:var(--text3);margin-top:2px"><?= $mc['last_at'] ? date('M j, g:ia',strtotime($mc['last_at'])) : '' ?></div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Thread -->
  <div class="msg-thread">
    <?php if (!$msg_cid || !$thread_contact): ?>
    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:var(--text3)">
      <div style="font-size:32px;margin-bottom:8px">💬</div>
      <div>Select a conversation</div>
    </div>
    <?php else: ?>
    <div style="padding:12px 16px;border-bottom:1px solid var(--border);flex-shrink:0;display:flex;align-items:center;justify-content:space-between">
      <div>
        <div style="font-size:14px;font-weight:700"><?= h($thread_contact['name']) ?><?= $thread_contact['company']?' <span style="font-size:12px;color:var(--text3);">'.h($thread_contact['company']).'</span>':'' ?></div>
        <div style="font-size:11.5px;color:var(--text3)"><?= h($thread_contact['email']) ?></div>
      </div>
      <a href="client_portal.php" target="_blank" style="font-size:12px;color:var(--text3)">View Portal ↗</a>
    </div>
    <div class="msg-thread-body">
      <?php foreach ($thread as $m):
        $is_staff = $m['sender_type'] === 'staff';
      ?>
      <div style="display:flex;flex-direction:column">
        <div style="font-size:10.5px;color:var(--text3);margin-bottom:3px;<?= $is_staff?'text-align:right':'padding-left:2px' ?>"><?= $is_staff ? h($m['staff_name']??'Staff') : h($thread_contact['name']) ?><?= $m['subject']?' · '.h($m['subject']):'' ?></div>
        <div class="bubble <?= $is_staff?'bubble-staff':'bubble-client' ?>"><?= nl2br(h($m['body'])) ?></div>
        <div style="font-size:10px;color:var(--text3);margin-top:2px;<?= $is_staff?'text-align:right':'padding-left:2px' ?>"><?= date('M j, Y g:ia',strtotime($m['created_at'])) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="msg-thread-reply">
      <form method="POST" style="display:flex;gap:8px;align-items:flex-end">
        <input type="hidden" name="action" value="reply_message">
        <input type="hidden" name="contact_id" value="<?= $msg_cid ?>">
        <div style="flex:1"><textarea name="body" class="form-control" style="min-height:60px;resize:none" placeholder="Reply to <?= h($thread_contact['name']) ?>…" required></textarea></div>
        <button type="submit" class="btn btn-primary" style="flex-shrink:0">Send ↑</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php endif; ?>

<?php renderLayoutEnd(); ?>