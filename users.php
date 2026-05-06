<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
requireRole(['admin','manager']);
$db = getCRMDB();
$user = currentUser();

ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        requireRole(['admin']);
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $role  = $_POST['role'] ?? 'member';
        $dept  = trim($_POST['department'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $dept_role = in_array($_POST['department_role'] ?? '', ['general','tele_caller','digital_marketing','software_developer','graphics_designer']) ? $_POST['department_role'] : 'general';
        if (!$name||!$email||!$pass) { flash('Name, email, and password required.','error'); ob_end_clean(); header('Location: users.php'); exit; }
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (name,email,password,role,department,phone,department_role) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssss",$name,$email,$hash,$role,$dept,$phone,$dept_role);
        if ($stmt->execute()) { logActivity('created user',$name,$db->insert_id); flash('User created.','success'); }
        else flash('Email already exists.','error');
        ob_end_clean(); header('Location: users.php'); exit;
    }

    if ($action === 'edit') {
        $id    = (int)$_POST['id'];
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = $_POST['role'] ?? 'member';
        $dept  = trim($_POST['department'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $stat  = $_POST['status'] ?? 'active';
        $dept_role = in_array($_POST['department_role'] ?? '', ['general','tele_caller','digital_marketing','software_developer','graphics_designer']) ? $_POST['department_role'] : 'general';
        // Only admin can change roles
        if (!isAdmin()) $role = $db->query("SELECT role FROM users WHERE id=$id")->fetch_row()[0];
        $stmt = $db->prepare("UPDATE users SET name=?,email=?,role=?,department=?,phone=?,status=?,department_role=? WHERE id=?");
        $stmt->bind_param("sssssssi",$name,$email,$role,$dept,$phone,$stat,$dept_role,$id);
        $stmt->execute();
        // Change password if provided
        if (!empty($_POST['new_password'])) {
            $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $db->query("UPDATE users SET password='$hash' WHERE id=$id");
        }
        flash('User updated.','success');
        ob_end_clean(); header('Location: users.php'); exit;
    }

    if ($action === 'delete' && isAdmin()) {
        $id = (int)$_POST['id'];
        if ($id == $user['id']) { flash('Cannot delete yourself.','error'); ob_end_clean(); header('Location: users.php'); exit; }
        $db->query("UPDATE users SET status='inactive' WHERE id=$id");
        flash('User deactivated.','success');
        ob_end_clean(); header('Location: users.php'); exit;
    }
}
ob_end_clean();

$edit_id = (int)($_GET['edit'] ?? 0);
$users = $db->query("
  SELECT u.*,
    (SELECT COUNT(*) FROM tasks WHERE assigned_to=u.id AND status!='done') AS open_tasks,
    (SELECT COUNT(*) FROM project_members WHERE user_id=u.id) AS proj_count
  FROM users u ORDER BY FIELD(u.role,'admin','manager','member'), u.name
")->fetch_all(MYSQLI_ASSOC);

$edit_user = null;
if ($edit_id) $edit_user = $db->query("SELECT * FROM users WHERE id=$edit_id")->fetch_assoc();

renderLayout('Team', 'users');
?>

<div style="display:flex;justify-content:flex-end;margin-bottom:16px">
  <?php if (isAdmin()): ?>
  <button class="btn btn-primary" onclick="openModal('modal-user')">＋ <span>Add Member</span></button>
  <?php endif; ?>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr>
        <th>Member</th><th>Role</th><th>Department</th><th>Status</th><th>Open Tasks</th><th>Projects</th><th>Last Login</th><th>Actions</th>
      </tr></thead>
      <tbody>
        <?php foreach ($users as $u):
          $rc=['admin'=>'#ef4444','manager'=>'#f59e0b','member'=>'#6366f1'][$u['role']] ?? '#94a3b8';
          $sc = $u['status']==='active'?'#10b981':'#ef4444';
          $init = implode('',array_map(fn($w)=>strtoupper($w[0]),explode(' ',$u['name'])));
          $init = substr($init,0,2);
        ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <div class="avatar" style="background:<?= $rc ?>">
                <?php if ($u['avatar']): ?>
                <img src="uploads/avatars/<?= h($u['avatar']) ?>" alt="">
                <?php else: echo h($init); endif; ?>
              </div>
              <div>
                <div style="font-weight:600;color:var(--text);font-size:13.5px"><?= h($u['name']) ?></div>
                <div style="font-size:11.5px;color:var(--text3)"><?= h($u['email']) ?></div>
              </div>
            </div>
          </td>
          <td><span class="badge" style="background:<?= $rc ?>20;color:<?= $rc ?>"><?= h($u['role']) ?></span></td>
          <td style="font-size:12.5px"><?= h($u['department'] ?? '—') ?></td>
          <td><span class="badge" style="background:<?= $sc ?>20;color:<?= $sc ?>"><?= h($u['status']) ?></span></td>
          <td style="text-align:center"><span style="font-weight:700;color:var(--orange)"><?= $u['open_tasks'] ?></span></td>
          <td style="text-align:center"><?= $u['proj_count'] ?></td>
          <td style="font-size:12px;color:var(--text3)"><?= $u['last_login'] ? fDate($u['last_login'],'M j, g:ia') : 'Never' ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <button class="btn btn-ghost btn-sm btn-icon" onclick="openEditUser(<?= htmlspecialchars(json_encode($u)) ?>)" title="Edit">✎</button>
              <?php if (isAdmin() && $u['id'] != $user['id']): ?>
              <form method="POST" onsubmit="return confirm('Deactivate this user?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Deactivate">🚫</button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- CREATE MODAL (admin only) -->
<?php if (isAdmin()): ?>
<div class="modal-overlay" id="modal-user">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Team Member</div>
      <button class="modal-close" onclick="closeModal('modal-user')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" required placeholder="First Last">
          </div>
          <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" required placeholder="user@thepadak.com">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Password *</label>
            <input type="password" name="password" class="form-control" required placeholder="Min 8 chars">
          </div>
          <div class="form-group">
            <label class="form-label">Role</label>
            <select name="role" class="form-control">
              <option value="member">Member</option>
              <option value="manager">Manager</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Department</label>
            <input type="text" name="department" class="form-control" placeholder="e.g. Development" list="dept-list">
            <datalist id="dept-list"><option>Development</option><option>Design</option><option>Marketing</option><option>Management</option><option>Business Development</option></datalist>
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" placeholder="+94 …">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Intern Specialization</label>
            <select name="department_role" class="form-control">
              <option value="general">General Member</option>
              <option value="tele_caller">📞 Tele Caller Intern</option>
              <option value="digital_marketing">📣 Digital Marketing Intern</option>
              <option value="software_developer">💻 Software Developer Intern</option>
              <option value="graphics_designer">🎨 Graphics Designer Intern</option>
            </select>
            <small style="color:var(--text3);font-size:11px">Only applies when role is "Member".</small>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-user')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Member</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- EDIT MODAL -->
<div class="modal-overlay <?= $edit_id?'open':'' ?>" id="modal-edit-user">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Member</div>
      <button class="modal-close" onclick="closeModal('modal-edit-user');<?= $edit_id?"location.href='users.php'":'' ?>">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="eu-id" value="<?= $edit_id ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" id="eu-name" class="form-control" required value="<?= h($edit_user['name']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" id="eu-email" class="form-control" required value="<?= h($edit_user['email']??'') ?>">
          </div>
        </div>
        <div class="form-row">
          <?php if (isAdmin()): ?>
          <div class="form-group">
            <label class="form-label">Role</label>
            <select name="role" id="eu-role" class="form-control">
              <?php foreach (['member','manager','admin'] as $r): ?>
              <option value="<?= $r ?>" <?= ($edit_user['role']??'')===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" id="eu-status" class="form-control">
              <option value="active" <?= ($edit_user['status']??'')==='active'?'selected':'' ?>>Active</option>
              <option value="inactive" <?= ($edit_user['status']??'')==='inactive'?'selected':'' ?>>Inactive</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Department</label>
            <input type="text" name="department" id="eu-dept" class="form-control" value="<?= h($edit_user['department']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" id="eu-phone" class="form-control" value="<?= h($edit_user['phone']??'') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Intern Specialization</label>
            <select name="department_role" id="eu-dept-role" class="form-control">
              <option value="general">General Member</option>
              <option value="tele_caller">📞 Tele Caller Intern</option>
              <option value="digital_marketing">📣 Digital Marketing Intern</option>
              <option value="software_developer">💻 Software Developer Intern</option>
              <option value="graphics_designer">🎨 Graphics Designer Intern</option>
            </select>
            <small style="color:var(--text3);font-size:11px">Only applies when role is "Member". Admin/Manager always see everything.</small>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">New Password (leave blank to keep current)</label>
          <input type="password" name="new_password" id="eu-pass" class="form-control" placeholder="Enter new password to change">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-edit-user');<?= $edit_id?"location.href='users.php'":'' ?>">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditUser(u){
  document.getElementById('eu-id').value=u.id;
  document.getElementById('eu-name').value=u.name||'';
  document.getElementById('eu-email').value=u.email||'';
  if(document.getElementById('eu-role'))document.getElementById('eu-role').value=u.role||'member';
  document.getElementById('eu-status').value=u.status||'active';
  document.getElementById('eu-dept').value=u.department||'';
  document.getElementById('eu-phone').value=u.phone||'';
  document.getElementById('eu-pass').value='';
  if(document.getElementById('eu-dept-role')) document.getElementById('eu-dept-role').value=u.department_role||'general';
  openModal('modal-edit-user');
}
<?php if ($edit_id): ?>document.addEventListener('DOMContentLoaded',()=>openModal('modal-edit-user'));<?php endif; ?>
</script>
<?php renderLayoutEnd(); ?>
