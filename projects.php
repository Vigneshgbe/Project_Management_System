<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db = getCRMDB();
$user = currentUser();

// Handle POST
ob_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $id       = (int)($_POST['id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $contact  = (int)($_POST['contact_id'] ?? 0) ?: null;
        $status   = $_POST['status'] ?? 'planning';
        $priority = $_POST['priority'] ?? 'medium';
        $start    = $_POST['start_date'] ?: null;
        $due      = $_POST['due_date'] ?: null;
        $budget   = $_POST['budget'] !== '' ? (float)$_POST['budget'] : null;
        $currency = $_POST['currency'] ?? 'LKR';
        $members  = $_POST['members'] ?? [];

        if (!$title) { flash('Title is required.','error'); ob_end_clean(); header('Location: projects.php'); exit; }

        if ($action === 'create') {
            $uid = $user['id'];
            $stmt = $db->prepare("INSERT INTO projects (title,description,contact_id,status,priority,start_date,due_date,budget,currency,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssisssssdi", $title,$desc,$contact,$status,$priority,$start,$due,$budget,$currency,$uid);
            $stmt->execute();
            $pid = $db->insert_id;
            if ($members) {
                $sm = $db->prepare("INSERT IGNORE INTO project_members (project_id,user_id) VALUES (?,?)");
                foreach ($members as $mid) { $mid=(int)$mid; $sm->bind_param("ii",$pid,$mid); $sm->execute(); }
            }
            logActivity('created project',$title,$pid);
            flash('Project created.','success');
        } else {
            if (!isManager()) { flash('Access denied.','error'); ob_end_clean(); header('Location: projects.php'); exit; }
            $stmt = $db->prepare("UPDATE projects SET title=?,description=?,contact_id=?,status=?,priority=?,start_date=?,due_date=?,budget=?,currency=? WHERE id=?");
            $stmt->bind_param("ssisssssdi", $title,$desc,$contact,$status,$priority,$start,$due,$budget,$currency,$id);
            $stmt->execute();
            $db->query("DELETE FROM project_members WHERE project_id=$id");
            if ($members) {
                $sm = $db->prepare("INSERT IGNORE INTO project_members (project_id,user_id) VALUES (?,?)");
                foreach ($members as $mid) { $mid=(int)$mid; $sm->bind_param("ii",$id,$mid); $sm->execute(); }
            }
            logActivity('updated project',$title,$id);
            flash('Project updated.','success');
        }
        ob_end_clean();
        header('Location: projects.php'); exit;
    }

    if ($action === 'delete' && isManager()) {
        $id = (int)($_POST['id'] ?? 0);
        $db->query("DELETE FROM projects WHERE id=$id");
        logActivity('deleted project','project',$id);
        flash('Project deleted.','success');
        ob_end_clean();
        header('Location: projects.php'); exit;
    }

    if ($action === 'progress' && isManager()) {
        $id = (int)$_POST['id'];
        $p  = max(0,min(100,(int)$_POST['progress']));
        $db->query("UPDATE projects SET progress=$p WHERE id=$id");
        ob_end_clean();
        header('Location: projects.php?view='.$id); exit;
    }
}
ob_end_clean();

// View single project
$view_id = (int)($_GET['view'] ?? 0);
$edit_id = (int)($_GET['edit'] ?? 0);

// Filters
$status_filter = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');

$where = "1=1";
if ($status_filter) $where .= " AND p.status='".$db->real_escape_string($status_filter)."'";
if ($search) $where .= " AND p.title LIKE '%".$db->real_escape_string($search)."%'";

$projects = $db->query("
  SELECT p.*, u.name AS creator, c.name AS client_name,
    (SELECT COUNT(*) FROM tasks WHERE project_id=p.id AND status='done') AS done_tasks,
    (SELECT COUNT(*) FROM tasks WHERE project_id=p.id) AS total_tasks,
    (SELECT COUNT(*) FROM project_members WHERE project_id=p.id) AS member_count
  FROM projects p
  LEFT JOIN users u ON u.id=p.created_by
  LEFT JOIN contacts c ON c.id=p.contact_id
  WHERE $where ORDER BY p.updated_at DESC
")->fetch_all(MYSQLI_ASSOC);

$contacts = $db->query("SELECT id,name,company FROM contacts WHERE status!='inactive' ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$all_users = $db->query("SELECT id,name,role FROM users WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Load project for editing
$edit_proj = null;
$edit_members = [];
if ($edit_id) {
    $edit_proj = $db->query("SELECT * FROM projects WHERE id=$edit_id")->fetch_assoc();
    $em = $db->query("SELECT user_id FROM project_members WHERE project_id=$edit_id")->fetch_all(MYSQLI_ASSOC);
    $edit_members = array_column($em,'user_id');
}

// Load single project view
$single = null;
$proj_tasks = [];
$proj_members_list = [];
$proj_docs = [];
if ($view_id) {
    $single = $db->query("SELECT p.*,c.name AS client_name,c.email AS client_email FROM projects p LEFT JOIN contacts c ON c.id=p.contact_id WHERE p.id=$view_id")->fetch_assoc();
    if ($single) {
        $proj_tasks = $db->query("SELECT t.*,u.name AS assignee FROM tasks t LEFT JOIN users u ON u.id=t.assigned_to WHERE t.project_id=$view_id ORDER BY FIELD(t.priority,'urgent','high','medium','low'),t.due_date")->fetch_all(MYSQLI_ASSOC);
        $proj_members_list = $db->query("SELECT u.id,u.name,u.role,u.avatar,pm.role AS pm_role FROM project_members pm JOIN users u ON u.id=pm.user_id WHERE pm.project_id=$view_id")->fetch_all(MYSQLI_ASSOC);
        $proj_docs = $db->query("SELECT d.*,u.name AS uploader FROM documents d LEFT JOIN users u ON u.id=d.uploaded_by WHERE d.project_id=$view_id ORDER BY d.created_at DESC")->fetch_all(MYSQLI_ASSOC);
    }
}

renderLayout('Projects', 'projects');
?>

<?php if ($single): // ============ SINGLE PROJECT VIEW ============ ?>
<div style="margin-bottom:16px">
  <a href="projects.php" style="color:var(--text3);font-size:13px">← Back to Projects</a>
</div>
<div style="display:grid;grid-template-columns:2fr 1fr;gap:18px;align-items:start">
  <!-- Left -->
  <div>
    <div class="card" style="margin-bottom:16px">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px">
        <div>
          <h2 style="font-family:var(--font-display);font-size:22px;font-weight:700;margin-bottom:6px"><?= h($single['title']) ?></h2>
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <?php $sc=statusColor($single['status']); $pc=statusColor($single['priority']); ?>
            <span class="badge" style="background:<?= $sc ?>20;color:<?= $sc ?>"><?= h(str_replace('_',' ',$single['status'])) ?></span>
            <span class="badge" style="background:<?= $pc ?>20;color:<?= $pc ?>"><?= priorityIcon($single['priority']) ?> <?= h($single['priority']) ?></span>
            <?php if ($single['client_name']): ?>
            <span style="font-size:12px;color:var(--text2)">👥 <?= h($single['client_name']) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <?php if (isManager()): ?>
        <div class="dropdown">
          <button class="btn btn-ghost btn-sm" onclick="toggleDropdown('proj-dd')">⋯</button>
          <div class="dropdown-menu" id="proj-dd">
            <a class="dropdown-item" href="projects.php?edit=<?= $single['id'] ?>">✎ Edit</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item danger" href="#" onclick="if(confirm('Delete project?'))document.getElementById('del-proj').submit()">🗑 Delete</a>
          </div>
          <form id="del-proj" method="POST" style="display:none"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $single['id'] ?>"></form>
        </div>
        <?php endif; ?>
      </div>
      <?php if ($single['description']): ?>
      <p style="color:var(--text2);font-size:13.5px;line-height:1.6;margin-bottom:16px"><?= nl2br(h($single['description'])) ?></p>
      <?php endif; ?>
      <!-- Progress -->
      <?php
        $ptotal = count($proj_tasks);
        $pdone  = count(array_filter($proj_tasks,fn($t)=>$t['status']==='done'));
        $ppct   = $ptotal > 0 ? round($pdone/$ptotal*100) : (int)$single['progress'];
      ?>
      <div style="margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px">
          <span style="font-size:12px;color:var(--text2);font-weight:600">PROGRESS</span>
          <span style="font-size:13px;font-weight:700"><?= $ppct ?>%</span>
        </div>
        <div class="progress-bar" style="height:8px"><div class="progress-fill" style="width:<?= $ppct ?>%"></div></div>
      </div>
      <!-- Meta grid -->
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
        <div style="background:var(--bg3);border-radius:8px;padding:12px">
          <div style="font-size:10px;color:var(--text3);margin-bottom:4px;text-transform:uppercase">Start</div>
          <div style="font-size:13px;font-weight:600"><?= fDate($single['start_date']) ?></div>
        </div>
        <div style="background:var(--bg3);border-radius:8px;padding:12px">
          <div style="font-size:10px;color:var(--text3);margin-bottom:4px;text-transform:uppercase">Due</div>
          <div style="font-size:13px;font-weight:600"><?= fDate($single['due_date']) ?></div>
        </div>
        <div style="background:var(--bg3);border-radius:8px;padding:12px">
          <div style="font-size:10px;color:var(--text3);margin-bottom:4px;text-transform:uppercase">Budget</div>
          <div style="font-size:13px;font-weight:600"><?= $single['budget'] ? h($single['currency']).' '.number_format($single['budget'],2) : '—' ?></div>
        </div>
      </div>
    </div>

    <!-- Tasks -->
    <div class="card" style="margin-bottom:16px">
      <div class="card-header">
        <div class="card-title">Tasks (<?= count($proj_tasks) ?>)</div>
        <a href="tasks.php?project_id=<?= $single['id'] ?>&new=1" class="btn btn-primary btn-sm">＋ Add Task</a>
      </div>
      <?php if (empty($proj_tasks)): ?>
        <div class="empty-state"><div class="icon">✅</div><p>No tasks yet.</p></div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Task</th><th>Assignee</th><th>Status</th><th>Due</th></tr></thead>
          <tbody>
            <?php foreach ($proj_tasks as $t): $sc=statusColor($t['status']); ?>
            <tr onclick="location.href='tasks.php?edit=<?= $t['id'] ?>'" style="cursor:pointer">
              <td class="td-main"><?= priorityIcon($t['priority']) ?> <?= h($t['title']) ?></td>
              <td><?= h($t['assignee'] ?? '—') ?></td>
              <td><span class="badge" style="background:<?= $sc ?>20;color:<?= $sc ?>"><?= h(str_replace('_',' ',$t['status'])) ?></span></td>
              <td><?= fDate($t['due_date']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Documents -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Documents (<?= count($proj_docs) ?>)</div>
        <a href="documents.php?project_id=<?= $single['id'] ?>&new=1" class="btn btn-ghost btn-sm">＋ Upload</a>
      </div>
      <?php if (empty($proj_docs)): ?>
        <div class="empty-state"><div class="icon">📄</div><p>No documents attached.</p></div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:8px">
          <?php foreach ($proj_docs as $d): ?>
          <div style="background:var(--bg3);border-radius:8px;padding:11px 14px;display:flex;align-items:center;gap:12px">
            <span style="font-size:22px">📄</span>
            <div style="flex:1;min-width:0">
              <div style="font-size:13px;font-weight:600;color:var(--text)"><?= h($d['title']) ?></div>
              <div style="font-size:11px;color:var(--text3)"><?= h($d['original_name']) ?> · <?= formatSize($d['file_size']) ?></div>
            </div>
            <a href="documents.php?download=<?= $d['id'] ?>" class="btn btn-ghost btn-sm">↓</a>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right -->
  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="card-title" style="margin-bottom:14px">Team Members</div>
      <?php if (empty($proj_members_list)): ?>
        <p style="font-size:13px;color:var(--text3)">No members assigned.</p>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px">
          <?php foreach ($proj_members_list as $m): ?>
          <div style="display:flex;align-items:center;gap:10px">
            <div class="avatar"><?= strtoupper(substr($m['name'],0,1)) ?></div>
            <div>
              <div style="font-size:13px;font-weight:600"><?= h($m['name']) ?></div>
              <div style="font-size:11px;color:var(--text3);text-transform:capitalize"><?= h($m['role']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-title" style="margin-bottom:14px">Quick Stats</div>
      <?php
        $todo = count(array_filter($proj_tasks,fn($t)=>$t['status']==='todo'));
        $inprog = count(array_filter($proj_tasks,fn($t)=>$t['status']==='in_progress'));
        $review = count(array_filter($proj_tasks,fn($t)=>$t['status']==='review'));
      ?>
      <?php foreach ([['To Do',$todo,'#6366f1'],['In Progress',$inprog,'#f59e0b'],['Review',$review,'#8b5cf6'],['Done',$pdone,'#10b981']] as [$l,$v,$c]): ?>
      <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
        <span style="font-size:13px;color:var(--text2)"><?= $l ?></span>
        <span style="font-size:13px;font-weight:700;color:<?= $c ?>"><?= $v ?></span>
      </div>
      <?php endforeach; ?>
      <div style="display:flex;justify-content:space-between;padding:8px 0">
        <span style="font-size:13px;color:var(--text2)">Total Tasks</span>
        <span style="font-size:13px;font-weight:700"><?= $ptotal ?></span>
      </div>
    </div>
  </div>
</div>

<?php else: // ============ PROJECT LIST ============ ?>

<div class="card-header" style="margin-bottom:16px">
  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;flex:1">
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <div class="search-box">
        <span style="color:var(--text3)">🔍</span>
        <input type="text" name="q" placeholder="Search projects…" value="<?= h($search) ?>">
      </div>
      <select name="status" class="form-control" style="width:auto;padding:7px 12px" onchange="this.form.submit()">
        <option value="">All Status</option>
        <?php foreach (['planning','active','on_hold','completed','cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= $status_filter===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
  <?php if (isManager()): ?>
  <button class="btn btn-primary" onclick="openModal('modal-project')">＋ <span>New Project</span></button>
  <?php endif; ?>
</div>

<?php if (empty($projects)): ?>
<div class="card"><div class="empty-state"><div class="icon">📁</div><p>No projects found. <?= isManager() ? '<a href="#" onclick="openModal(\'modal-project\')" style="color:var(--orange)">Create the first one</a>' : '' ?></p></div></div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:14px">
  <?php foreach ($projects as $p):
    $progress = $p['total_tasks'] > 0 ? round($p['done_tasks']/$p['total_tasks']*100) : (int)$p['progress'];
    $sc=statusColor($p['status']); $pc=statusColor($p['priority']);
  ?>
  <div class="card" style="cursor:pointer;transition:border-color .15s" onmouseover="this.style.borderColor='var(--border2)'" onmouseout="this.style.borderColor='var(--border)'" onclick="location.href='projects.php?view=<?= $p['id'] ?>'">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px">
      <div>
        <div style="font-size:15px;font-weight:700;font-family:var(--font-display);margin-bottom:5px"><?= h($p['title']) ?></div>
        <span class="badge" style="background:<?= $sc ?>20;color:<?= $sc ?>"><?= h(str_replace('_',' ',$p['status'])) ?></span>
      </div>
      <span style="font-size:18px"><?= priorityIcon($p['priority']) ?></span>
    </div>
    <?php if ($p['client_name']): ?>
    <div style="font-size:12px;color:var(--text2);margin-bottom:10px">👥 <?= h($p['client_name']) ?></div>
    <?php endif; ?>
    <div style="margin-bottom:12px">
      <div style="display:flex;justify-content:space-between;margin-bottom:4px">
        <span style="font-size:11px;color:var(--text3)">Progress</span>
        <span style="font-size:11px;font-weight:600"><?= $progress ?>%</span>
      </div>
      <div class="progress-bar"><div class="progress-fill" style="width:<?= $progress ?>%"></div></div>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center">
      <span style="font-size:11.5px;color:var(--text3)">📋 <?= $p['total_tasks'] ?> tasks · 👥 <?= $p['member_count'] ?></span>
      <span style="font-size:11.5px;color:var(--text3)"><?= $p['due_date'] ? 'Due '.fDate($p['due_date']) : 'No deadline' ?></span>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- CREATE/EDIT MODAL -->
<div class="modal-overlay <?= $edit_id?'open':'' ?>" id="modal-project">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><?= $edit_id ? 'Edit Project' : 'New Project' ?></div>
      <button class="modal-close" onclick="closeModal('modal-project');<?= $edit_id ? "location.href='projects.php'" : '' ?>">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="<?= $edit_id ? 'edit' : 'create' ?>">
      <?php if ($edit_id): ?><input type="hidden" name="id" value="<?= $edit_id ?>"><?php endif; ?>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Project Title *</label>
          <input type="text" name="title" class="form-control" required value="<?= h($edit_proj['title'] ?? '') ?>" placeholder="e.g. SDMI Brand Campaign">
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" placeholder="Project overview…"><?= h($edit_proj['description'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Client / Contact</label>
            <select name="contact_id" class="form-control">
              <option value="">— None —</option>
              <?php foreach ($contacts as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($edit_proj['contact_id']??'')==$c['id']?'selected':'' ?>><?= h($c['name']) ?><?= $c['company']?' ('.$c['company'].')':'' ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Priority</label>
            <select name="priority" class="form-control">
              <?php foreach (['low','medium','high','urgent'] as $p): ?>
              <option value="<?= $p ?>" <?= ($edit_proj['priority']??'medium')===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <?php foreach (['planning','active','on_hold','completed','cancelled'] as $s): ?>
              <option value="<?= $s ?>" <?= ($edit_proj['status']??'planning')===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Currency</label>
            <select name="currency" class="form-control">
              <?php foreach (['LKR','USD','EUR','GBP'] as $c): ?>
              <option <?= ($edit_proj['currency']??'LKR')===$c?'selected':'' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" class="form-control" value="<?= h($edit_proj['start_date']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Due Date</label>
            <input type="date" name="due_date" class="form-control" value="<?= h($edit_proj['due_date']??'') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Budget</label>
          <input type="number" name="budget" step="0.01" class="form-control" value="<?= h($edit_proj['budget']??'') ?>" placeholder="0.00">
        </div>
        <div class="form-group">
          <label class="form-label">Team Members</label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:10px">
            <?php foreach ($all_users as $u): ?>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text2);cursor:pointer;padding:4px">
              <input type="checkbox" name="members[]" value="<?= $u['id'] ?>" <?= in_array($u['id'],$edit_members)?'checked':'' ?> style="accent-color:var(--orange)">
              <?= h($u['name']) ?> <span style="font-size:10px;color:var(--text3)"><?= h($u['role']) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-project');<?= $edit_id ? "location.href='projects.php'" : '' ?>">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= $edit_id ? 'Save Changes' : 'Create Project' ?></button>
      </div>
    </form>
  </div>
</div>

<?php if ($edit_id): ?>
<script>document.addEventListener('DOMContentLoaded',()=>openModal('modal-project'))</script>
<?php endif; ?>

<?php endif; // end list view ?>

<?php renderLayoutEnd(); ?>
