<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $id      = (int)($_POST['id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $type    = $_POST['type'] ?? 'lead';
        $status  = $_POST['status'] ?? 'prospect';
        $notes   = trim($_POST['notes'] ?? '');
        $assign  = (int)($_POST['assigned_to'] ?? 0) ?: null;
        $uid     = $user['id'];
        if (!$name) { flash('Name required.','error'); ob_end_clean(); header('Location: contacts.php'); exit; }
        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO contacts (name,company,email,phone,address,type,status,notes,assigned_to,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssssssii",$name,$company,$email,$phone,$address,$type,$status,$notes,$assign,$uid);
            $stmt->execute();
            logActivity('created contact',$name,$db->insert_id);
            flash('Contact created.','success');
        } else {
            if (!isManager() && $uid !== (int)($db->query("SELECT created_by FROM contacts WHERE id=$id")->fetch_row()[0]??0)) {
                flash('Access denied.','error'); ob_end_clean(); header('Location: contacts.php'); exit;
            }
            $stmt = $db->prepare("UPDATE contacts SET name=?,company=?,email=?,phone=?,address=?,type=?,status=?,notes=?,assigned_to=? WHERE id=?");
            $stmt->bind_param("ssssssssii",$name,$company,$email,$phone,$address,$type,$status,$notes,$assign,$id);
            $stmt->execute();
            logActivity('updated contact',$name,$id);
            flash('Contact updated.','success');
        }
        ob_end_clean(); header('Location: contacts.php'); exit;
    }
    if ($action === 'delete' && isManager()) {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM contacts WHERE id=$id");
        flash('Contact deleted.','success');
        ob_end_clean(); header('Location: contacts.php'); exit;
    }
}
ob_end_clean();

$edit_id  = (int)($_GET['edit'] ?? 0);
$type_f   = $_GET['type'] ?? '';
$status_f = $_GET['status'] ?? '';
$search   = trim($_GET['q'] ?? '');

$where = "1=1";
// Members see only contacts assigned to them or created by them
if (!isManager()) {
    $where .= " AND (c.assigned_to = $uid OR c.created_by = $uid)";
}
if ($type_f)   $where .= " AND c.type='".$db->real_escape_string($type_f)."'";
if ($status_f) $where .= " AND c.status='".$db->real_escape_string($status_f)."'";
if ($search)   $where .= " AND (c.name LIKE '%".$db->real_escape_string($search)."%' OR c.company LIKE '%".$db->real_escape_string($search)."%' OR c.email LIKE '%".$db->real_escape_string($search)."%')";

$contacts = $db->query("
    SELECT c.*, u.name AS assignee_name,
        (SELECT COUNT(*) FROM projects WHERE contact_id=c.id) AS proj_count
    FROM contacts c
    LEFT JOIN users u ON u.id=c.assigned_to
    WHERE $where ORDER BY c.updated_at DESC
")->fetch_all(MYSQLI_ASSOC);

$all_users = $db->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$edit_contact = null;
if ($edit_id) $edit_contact = $db->query("SELECT * FROM contacts WHERE id=$edit_id")->fetch_assoc();

// Stats — scoped to same role filter so numbers match what user sees
$stats_scope = isManager()
    ? "1=1"
    : "(assigned_to = $uid OR created_by = $uid)";
$type_stats = [];
foreach (['client','lead','partner','vendor'] as $t) {
    $type_stats[$t] = $db->query("SELECT COUNT(*) FROM contacts WHERE type='$t' AND ($stats_scope)")->fetch_row()[0];
}

renderLayout('Contacts', 'contacts');
?>

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
  <?php $icons=['client'=>'🤝','lead'=>'🎯','partner'=>'🌐','vendor'=>'🏭']; $colors=['client'=>'#10b981','lead'=>'#f97316','partner'=>'#6366f1','vendor'=>'#f59e0b']; ?>
  <?php foreach ($type_stats as $t=>$n): ?>
  <div class="stat-card" style="cursor:pointer" onclick="document.querySelector('[name=type]').value='<?= $t ?>';document.getElementById('filter-form').submit()">
    <div class="stat-icon" style="background:<?= $colors[$t] ?>20"><?= $icons[$t] ?></div>
    <div><div class="stat-val"><?= $n ?></div><div class="stat-lbl"><?= ucfirst($t) ?>s</div></div>
  </div>
  <?php endforeach; ?>
</div>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;flex-wrap:wrap">
  <form id="filter-form" method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <div class="search-box">
      <span style="color:var(--text3)">🔍</span>
      <input type="text" name="q" placeholder="Search contacts…" value="<?= h($search) ?>">
    </div>
    <select name="type" class="form-control" style="width:auto" onchange="this.form.submit()">
      <option value="">All Types</option>
      <?php foreach (['client','lead','partner','vendor'] as $t): ?>
      <option value="<?= $t ?>" <?= $type_f===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="form-control" style="width:auto" onchange="this.form.submit()">
      <option value="">All Status</option>
      <?php foreach (['active','inactive','prospect'] as $s): ?>
      <option value="<?= $s ?>" <?= $status_f===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <?php if (isManager()): ?>
  <button class="btn btn-primary" onclick="openModal('modal-contact')">＋ <span>Add Contact</span></button>
  <?php endif; ?>
</div>

<?php if (empty($contacts)): ?>
<div class="card"><div class="empty-state"><div class="icon">👥</div><p>No contacts yet.</p></div></div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr>
        <th>Name</th><th>Company</th><th>Type</th><th>Status</th><th>Email</th><th>Phone</th><th>Projects</th><th>Assigned To</th><th>Actions</th>
      </tr></thead>
      <tbody>
        <?php foreach ($contacts as $c):
          $tc=statusColor($c['type']); $sc=statusColor($c['status']);
          $initials = implode('',array_map(fn($w)=>strtoupper($w[0]),explode(' ',$c['name'])));
          $initials  = substr($initials,0,2);
        ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:9px">
              <div class="avatar" style="background:<?= $tc ?>;width:30px;height:30px;font-size:11px;flex-shrink:0"><?= h($initials) ?></div>
              <span class="td-main"><?= h($c['name']) ?></span>
            </div>
          </td>
          <td><?= h($c['company'] ?? '—') ?></td>
          <td><span class="badge" style="background:<?= $tc ?>20;color:<?= $tc ?>"><?= h($c['type']) ?></span></td>
          <td><span class="badge" style="background:<?= $sc ?>20;color:<?= $sc ?>"><?= h($c['status']) ?></span></td>
          <td><?= $c['email'] ? '<a href="mailto:'.h($c['email']).'" style="color:var(--orange);font-size:12.5px">'.h($c['email']).'</a>' : '—' ?></td>
          <td style="font-size:12.5px"><?= h($c['phone'] ?? '—') ?></td>
          <td style="text-align:center"><span style="font-weight:700;color:var(--orange)"><?= $c['proj_count'] ?></span></td>
          <td style="font-size:12.5px"><?= h($c['assignee_name'] ?? '—') ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <?php if (isManager()): ?>
              <button class="btn btn-ghost btn-sm btn-icon" onclick="openEditContact(<?= htmlspecialchars(json_encode($c)) ?>)" title="Edit">✎</button>
              <form method="POST" onsubmit="return confirm('Delete contact?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete">🗑</button>
              </form>
              <?php else: ?>
              <button class="btn btn-ghost btn-sm btn-icon" onclick="openEditContact(<?= htmlspecialchars(json_encode($c)) ?>)" title="View Details" style="cursor:default;opacity:.6" disabled>👁</button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- MODAL -->
<div class="modal-overlay <?= $edit_id?'open':'' ?>" id="modal-contact">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="contact-modal-title">Add Contact</div>
      <button class="modal-close" onclick="closeModal('modal-contact');<?= $edit_id?"location.href='contacts.php'":'' ?>">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" id="contact-action" value="<?= $edit_id?'edit':'create' ?>">
      <input type="hidden" name="id" id="contact-id" value="<?= $edit_id ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" id="c-name" class="form-control" required value="<?= h($edit_contact['name']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Company</label>
            <input type="text" name="company" id="c-company" class="form-control" value="<?= h($edit_contact['company']??'') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" id="c-email" class="form-control" value="<?= h($edit_contact['email']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" id="c-phone" class="form-control" value="<?= h($edit_contact['phone']??'') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Type</label>
            <select name="type" id="c-type" class="form-control">
              <?php foreach (['client','lead','partner','vendor'] as $t): ?>
              <option value="<?= $t ?>" <?= ($edit_contact['type']??'lead')===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" id="c-status" class="form-control">
              <?php foreach (['prospect','active','inactive'] as $s): ?>
              <option value="<?= $s ?>" <?= ($edit_contact['status']??'prospect')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Address</label>
          <textarea name="address" id="c-address" class="form-control" style="min-height:60px"><?= h($edit_contact['address']??'') ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Assign To</label>
          <select name="assigned_to" id="c-assign" class="form-control">
            <option value="">— Unassigned —</option>
            <?php foreach ($all_users as $u): ?>
            <option value="<?= $u['id'] ?>" <?= ($edit_contact['assigned_to']??'')==$u['id']?'selected':'' ?>><?= h($u['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" id="c-notes" class="form-control"><?= h($edit_contact['notes']??'') ?></textarea>
        </div>
      </div>
      <?php if ($edit_id): ?>
      <div style="border-top:1px solid var(--border);padding:14px 16px 0">
        <?php renderAttachWidget('contact', $edit_id); ?>
      </div>
      <?php endif; ?>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-contact');<?= $edit_id?"location.href='contacts.php'":'' ?>">Cancel</button>
        <button type="submit" id="contact-submit" class="btn btn-primary"><?= $edit_id?'Save Changes':'Add Contact' ?></button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditContact(c){
  document.getElementById('contact-modal-title').textContent='Edit Contact';
  document.getElementById('contact-action').value='edit';
  document.getElementById('contact-id').value=c.id;
  document.getElementById('c-name').value=c.name||'';
  document.getElementById('c-company').value=c.company||'';
  document.getElementById('c-email').value=c.email||'';
  document.getElementById('c-phone').value=c.phone||'';
  document.getElementById('c-type').value=c.type||'lead';
  document.getElementById('c-status').value=c.status||'prospect';
  document.getElementById('c-address').value=c.address||'';
  document.getElementById('c-assign').value=c.assigned_to||'';
  document.getElementById('c-notes').value=c.notes||'';
  document.getElementById('contact-submit').textContent='Save Changes';
  openModal('modal-contact');
}
<?php if ($edit_id): ?>document.addEventListener('DOMContentLoaded',()=>openModal('modal-contact'));<?php endif; ?>
</script>
<?php renderLayoutEnd(); ?>