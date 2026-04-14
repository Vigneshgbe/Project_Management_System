<?php
// profile.php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db = getCRMDB();
$user = currentUser();

ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $user['id'];

    if ($action === 'update_profile') {
        $name  = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $dept  = trim($_POST['department'] ?? '');
        if (!$name) { flash('Name required.','error'); ob_end_clean(); header('Location: profile.php'); exit; }

        // Avatar upload
        $avatar = null;
        if (!empty($_FILES['avatar']['name'])) {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (in_array($ext,['jpg','jpeg','png','gif','webp'])) {
                $fname = 'avatar_'.$id.'_'.time().'.'.$ext;
                if (!is_dir(UPLOAD_AVATAR_DIR)) mkdir(UPLOAD_AVATAR_DIR, 0755, true);
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], UPLOAD_AVATAR_DIR.$fname)) {
                    $avatar = $fname;
                    $_SESSION['crm_avatar'] = $fname;
                }
            }
        }

        if ($avatar) {
            $stmt = $db->prepare("UPDATE users SET name=?,phone=?,department=?,avatar=? WHERE id=?");
            $stmt->bind_param("ssssi",$name,$phone,$dept,$avatar,$id);
        } else {
            $stmt = $db->prepare("UPDATE users SET name=?,phone=?,department=? WHERE id=?");
            $stmt->bind_param("sssi",$name,$phone,$dept,$id);
        }
        $stmt->execute();
        $_SESSION['crm_name'] = $name;
        flash('Profile updated.','success');
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $row = $db->query("SELECT password FROM users WHERE id=$id")->fetch_assoc();
        if (!password_verify($current, $row['password'])) { flash('Current password incorrect.','error'); ob_end_clean(); header('Location: profile.php'); exit; }
        if (strlen($new) < 6) { flash('New password must be at least 6 chars.','error'); ob_end_clean(); header('Location: profile.php'); exit; }
        if ($new !== $confirm) { flash('Passwords do not match.','error'); ob_end_clean(); header('Location: profile.php'); exit; }
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $db->query("UPDATE users SET password='$hash' WHERE id=$id");
        flash('Password changed.','success');
    }
    ob_end_clean(); header('Location: profile.php'); exit;
}
ob_end_clean();

$profile = $db->query("SELECT * FROM users WHERE id=".(int)$user['id'])->fetch_assoc();
$my_tasks = $db->query("SELECT COUNT(*) FROM tasks WHERE assigned_to=".(int)$user['id']." AND status!='done'")->fetch_row()[0];
$done_tasks = $db->query("SELECT COUNT(*) FROM tasks WHERE assigned_to=".(int)$user['id']." AND status='done'")->fetch_row()[0];
$projs = $db->query("SELECT COUNT(*) FROM project_members WHERE user_id=".(int)$user['id'])->fetch_row()[0];
$init = implode('',array_map(fn($w)=>strtoupper($w[0]),explode(' ',$profile['name'])));
$init = substr($init,0,2);

renderLayout('My Profile', 'profile');
?>
<div style="max-width:720px;margin:0 auto">
  <!-- Header card -->
  <div class="card" style="margin-bottom:16px">
    <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
      <div style="position:relative">
        <div class="avatar" style="width:72px;height:72px;font-size:26px;font-weight:700">
          <?php if ($profile['avatar']): ?>
          <img src="uploads/avatars/<?= h($profile['avatar']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
          <?php else: echo h($init); endif; ?>
        </div>
      </div>
      <div>
        <div style="font-family:var(--font-display);font-size:22px;font-weight:700"><?= h($profile['name']) ?></div>
        <div style="color:var(--text2);font-size:13.5px;margin-top:2px"><?= h($profile['email']) ?></div>
        <div style="margin-top:6px;display:flex;gap:8px">
          <?php $rc=['admin'=>'#ef4444','manager'=>'#f59e0b','member'=>'#6366f1'][$profile['role']]??'#94a3b8'; ?>
          <span class="badge" style="background:<?= $rc ?>20;color:<?= $rc ?>"><?= h($profile['role']) ?></span>
          <?php if ($profile['department']): ?>
          <span class="badge" style="background:var(--bg3);color:var(--text2)"><?= h($profile['department']) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <div style="margin-left:auto;display:flex;gap:16px;text-align:center">
        <div><div style="font-size:22px;font-weight:700;font-family:var(--font-display);color:var(--orange)"><?= $my_tasks ?></div><div style="font-size:11px;color:var(--text3)">Open Tasks</div></div>
        <div><div style="font-size:22px;font-weight:700;font-family:var(--font-display);color:var(--green)"><?= $done_tasks ?></div><div style="font-size:11px;color:var(--text3)">Done</div></div>
        <div><div style="font-size:22px;font-weight:700;font-family:var(--font-display);color:var(--blue)"><?= $projs ?></div><div style="font-size:11px;color:var(--text3)">Projects</div></div>
      </div>
    </div>
  </div>

  <!-- Edit profile -->
  <div class="card" style="margin-bottom:16px">
    <div class="card-title" style="margin-bottom:16px">Edit Profile</div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="update_profile">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" name="name" class="form-control" value="<?= h($profile['name']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= h($profile['phone']??'') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Department</label>
          <input type="text" name="department" class="form-control" value="<?= h($profile['department']??'') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Profile Photo</label>
          <input type="file" name="avatar" class="form-control" accept="image/*">
        </div>
      </div>
      <div style="text-align:right">
        <button type="submit" class="btn btn-primary">Save Profile</button>
      </div>
    </form>
  </div>

  <!-- Change password -->
  <div class="card">
    <div class="card-title" style="margin-bottom:16px">Change Password</div>
    <form method="POST">
      <input type="hidden" name="action" value="change_password">
      <div class="form-group">
        <label class="form-label">Current Password</label>
        <input type="password" name="current_password" class="form-control" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">New Password</label>
          <input type="password" name="new_password" class="form-control" required minlength="6">
        </div>
        <div class="form-group">
          <label class="form-label">Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>
      </div>
      <div style="text-align:right">
        <button type="submit" class="btn btn-primary">Change Password</button>
      </div>
    </form>
  </div>
</div>
<?php renderLayoutEnd(); ?>