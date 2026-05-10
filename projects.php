<?php
require_once 'config.php';
require_once 'includes/layout.php';
require_once 'includes/attach_widget.php';
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
            $stmt->bind_param("ssissssdsi", $title,$desc,$contact,$status,$priority,$start,$due,$budget,$currency,$uid);
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
            $stmt->bind_param("ssissssdsi", $title,$desc,$contact,$status,$priority,$start,$due,$budget,$currency,$id);
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
if ($search)        $where .= " AND p.title LIKE '%".$db->real_escape_string($search)."%'";

// Members/interns see only projects they are a member of or created
if (!isManager()) {
    $where .= " AND (p.created_by = $uid OR EXISTS (SELECT 1 FROM project_members pm WHERE pm.project_id=p.id AND pm.user_id=$uid))";
}

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
    // Members can only view projects they belong to
    $proj_access = isManager()
        ? "p.id=$view_id"
        : "p.id=$view_id AND (p.created_by=$uid OR EXISTS (SELECT 1 FROM project_members pm WHERE pm.project_id=p.id AND pm.user_id=$uid))";
    $single = $db->query("SELECT p.*,c.name AS client_name,c.email AS client_email FROM projects p LEFT JOIN contacts c ON c.id=p.contact_id WHERE $proj_access")->fetch_assoc();
    // If member tries to access a project they don't belong to, redirect
    if (!$single && $view_id) { header('Location: projects.php'); exit; }
    
    if ($single) {
        $proj_tasks = $db->query("SELECT t.*,u.name AS assignee FROM tasks t LEFT JOIN users u ON u.id=t.assigned_to WHERE t.project_id=$view_id ORDER BY FIELD(t.priority,'urgent','high','medium','low'),t.due_date")->fetch_all(MYSQLI_ASSOC);
        $proj_members_list = $db->query("SELECT u.id,u.name,u.role,u.avatar,pm.role AS pm_role FROM project_members pm JOIN users u ON u.id=pm.user_id WHERE pm.project_id=$view_id")->fetch_all(MYSQLI_ASSOC);
        $proj_docs = $db->query("SELECT d.*,u.name AS uploader FROM documents d LEFT JOIN users u ON u.id=d.uploaded_by WHERE d.project_id=$view_id ORDER BY d.created_at DESC")->fetch_all(MYSQLI_ASSOC);
    }
}

renderLayout('Projects', 'projects');
?>

<?php
// Helper: currency symbol
function currSymbol(string $c): string {
    return match($c) {
        'USD' => '$', 'EUR' => '€', 'GBP' => '£',
        'INR' => '₹', 'LKR' => 'Rs.',
        default => h($c).' '
    };
}
?>

<style>
/* ══ PROJECT LIST ══ */
.proj-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}

.proj-card{
  background:var(--bg2);border:1px solid var(--border);
  border-radius:var(--radius-lg);overflow:hidden;cursor:pointer;
  display:flex;flex-direction:column;
  transition:border-color .2s,box-shadow .2s,transform .15s;
  position:relative;
}
.proj-card::before{
  content:'';position:absolute;top:0;left:0;right:0;height:3px;
  background:var(--pc,#f97316);border-radius:var(--radius-lg) var(--radius-lg) 0 0;
}
.proj-card:hover{
  border-color:var(--pc,var(--border2));
  box-shadow:0 8px 32px rgba(0,0,0,.22);
  transform:translateY(-2px);
}
.proj-card:active{transform:translateY(0)}
.proj-card-top{padding:20px 18px 14px;flex:1}
.proj-card-bottom{
  padding:10px 18px;border-top:1px solid var(--border);
  display:flex;justify-content:space-between;align-items:center;
  background:var(--bg3);gap:8px;
}

/* ══ PROGRESS CONTROL ══ */
.prog-wrap{display:flex;align-items:center;gap:10px;margin-bottom:0}
.prog-bar-bg{
  flex:1;height:8px;background:var(--bg4);
  border-radius:99px;overflow:hidden;cursor:pointer;
}
.prog-bar-fill{
  height:100%;background:var(--orange);
  border-radius:99px;transition:width .3s ease,background .3s ease;
}
.prog-input{
  width:58px;background:var(--bg3);border:1.5px solid var(--border);
  border-radius:var(--radius-sm);padding:5px 8px;
  color:var(--text);font-size:12px;text-align:center;
  transition:border-color .15s;
  -moz-appearance:textfield;
}
.prog-input:focus{outline:none;border-color:var(--orange)}
.prog-input::-webkit-inner-spin-button,
.prog-input::-webkit-outer-spin-button{-webkit-appearance:none;margin:0}

/* ══ SINGLE PROJECT VIEW ══ */
.sp-grid{display:grid;grid-template-columns:1fr 280px;gap:18px;align-items:start}
.sp-meta{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:18px}
.sp-meta-box{
  background:var(--bg3);border:1px solid var(--border);
  border-radius:10px;padding:13px 14px;
  transition:border-color .15s;
}
.sp-meta-box:hover{border-color:var(--border2)}
.sp-meta-lbl{font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px}
.sp-meta-val{font-size:14px;font-weight:700;color:var(--text)}
.task-tr{transition:background .12s}
.task-tr:hover{background:var(--bg3)!important}
.mem-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)}
.mem-row:last-child{border-bottom:none}
.sp-section-title{font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;display:flex;align-items:center;gap:6px}
.stat-pill{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--border)}
.stat-pill:last-child{border-bottom:none}
.stat-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}

@media(max-width:960px){.sp-grid{grid-template-columns:1fr}.sp-meta{grid-template-columns:1fr 1fr}}
@media(max-width:768px){.proj-list{grid-template-columns:1fr}.sp-meta{grid-template-columns:1fr 1fr}}
@media(max-width:480px){.sp-meta{grid-template-columns:1fr 1fr}.proj-card-top{padding:16px 14px 12px}}
}
</style>

<?php if ($single): // ════ SINGLE PROJECT VIEW ════

  $ptotal    = count($proj_tasks);
  $pdone     = count(array_filter($proj_tasks,fn($t)=>$t['status']==='done'));
  // Always use the manually saved progress value from DB
  $ppct      = (int)$single['progress'];
  $sc     = statusColor($single['status']);
  $pc     = statusColor($single['priority']);
  $sym    = currSymbol($single['currency']??'LKR');
?>
<div style="margin-bottom:16px">
  <a href="projects.php" style="color:var(--text3);font-size:13px">← Back to Projects</a>
</div>
<div class="sp-grid">
  <!-- LEFT -->
  <div>
    <div class="card" style="margin-bottom:16px">
      <!-- Header -->
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px">
        <div style="flex:1;min-width:0">
          <h2 style="font-family:var(--font-display);font-size:21px;font-weight:700;margin-bottom:8px;line-height:1.2"><?= h($single['title']) ?></h2>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <span class="badge" style="background:<?= $sc ?>20;color:<?= $sc ?>"><?= h(str_replace('_',' ',$single['status'])) ?></span>
            <span class="badge" style="background:<?= $pc ?>20;color:<?= $pc ?>"><?= priorityIcon($single['priority']) ?> <?= ucfirst($single['priority']) ?></span>
            <?php if ($single['client_name']): ?>
            <span style="font-size:12px;color:var(--text2)">🏢 <?= h($single['client_name']) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <?php if (isManager()): ?>
        <div class="dropdown" style="flex-shrink:0">
          <button class="btn btn-ghost btn-sm" onclick="toggleDropdown('proj-dd')">⋯</button>
          <div class="dropdown-menu" id="proj-dd">
            <a class="dropdown-item" href="projects.php?edit=<?= $single['id'] ?>">✎ Edit Project</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item danger" href="#" onclick="if(confirm('Delete this project and all its data?'))document.getElementById('del-proj').submit()">🗑 Delete</a>
          </div>
          <form id="del-proj" method="POST" style="display:none">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $single['id'] ?>">
          </form>
        </div>
        <?php endif; ?>
      </div>

      <?php if ($single['description']): ?>
      <p style="color:var(--text2);font-size:13.5px;line-height:1.7;margin-bottom:16px;padding:12px;background:var(--bg3);border-radius:8px"><?= nl2br(h($single['description'])) ?></p>
      <?php endif; ?>

      <!-- Meta boxes -->
      <div class="sp-meta">
        <div class="sp-meta-box">
          <div class="sp-meta-lbl">Start Date</div>
          <div class="sp-meta-val"><?= fDate($single['start_date']) ?></div>
        </div>
        <div class="sp-meta-box">
          <div class="sp-meta-lbl">Due Date</div>
          <div class="sp-meta-val" <?php if ($single['due_date'] && $single['due_date'] < date('Y-m-d') && $single['status'] !== 'completed') echo 'style="color:var(--red)"'; ?>><?= fDate($single['due_date']) ?></div>
        </div>
        <div class="sp-meta-box">
          <div class="sp-meta-lbl">Budget</div>
          <div class="sp-meta-val"><?= $single['budget'] ? $sym.number_format((float)$single['budget'],2) : '—' ?></div>
        </div>
        <div class="sp-meta-box">
          <div class="sp-meta-lbl">Tasks Done</div>
          <div class="sp-meta-val"><span style="color:var(--green)"><?= $pdone ?></span> / <?= $ptotal ?></div>
        </div>
      </div>

      <!-- Progress with inline edit -->
      <div style="margin-bottom:6px;display:flex;justify-content:space-between;align-items:center">
        <span style="font-size:11px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.06em">Progress</span>
        <span style="font-size:13px;font-weight:700;color:var(--orange)" id="pct-display"><?= $ppct ?>%</span>
      </div>
      <?php if (isManager()): ?>
      <form method="POST" id="prog-form">
        <input type="hidden" name="action" value="progress">
        <input type="hidden" name="id" value="<?= $single['id'] ?>">
        <div class="prog-wrap">
          <div class="prog-bar-bg" onclick="clickBar(event,this)" style="cursor:pointer">
            <div class="prog-bar-fill" id="prog-fill" style="width:<?= $ppct ?>%"></div>
          </div>
          <!-- Single input — name="progress" directly, no hidden field needed -->
          <input type="number" class="prog-input" id="prog-val" name="progress"
            value="<?= $ppct ?>" min="0" max="100"
            oninput="syncProgress(this.value)">
          <button type="submit" class="btn btn-primary btn-sm">Save</button>
        </div>
      </form>
      <?php else: ?>
      <div class="prog-wrap">
        <div class="prog-bar-bg">
          <div class="prog-bar-fill" style="width:<?= $ppct ?>%"></div>
        </div>
        <span style="font-size:13px;font-weight:700;color:var(--orange)"><?= $ppct ?>%</span>
      </div>
      <?php endif; ?>
    </div>

    <!-- Tasks -->
    <div class="card" style="margin-bottom:16px">
      <div class="card-header">
        <div class="card-title">Tasks <span style="font-size:13px;color:var(--text3);font-weight:400">(<?= $ptotal ?>)</span></div>
        <a href="tasks.php?project_id=<?= $single['id'] ?>&new=1" class="btn btn-primary btn-sm">＋ Task</a>
      </div>
      <?php if (empty($proj_tasks)): ?>
        <div class="empty-state"><div class="icon">✅</div><p>No tasks yet.</p></div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Task</th><th>Assignee</th><th>Status</th><th>Due</th></tr></thead>
          <tbody>
            <?php foreach ($proj_tasks as $t):
              $ts = statusColor($t['status']);
              $overdue = $t['due_date'] && $t['due_date'] < date('Y-m-d') && $t['status'] !== 'done';
            ?>
            <tr class="task-tr" onclick="location.href='tasks.php?edit=<?= $t['id'] ?>'" style="cursor:pointer">
              <td class="td-main"><?= priorityIcon($t['priority']) ?> <?= h($t['title']) ?></td>
              <td style="font-size:12.5px;color:var(--text2)"><?= h($t['assignee'] ?? '—') ?></td>
              <td><span class="badge" style="background:<?= $ts ?>18;color:<?= $ts ?>;font-size:11px"><?= h(str_replace('_',' ',$t['status'])) ?></span></td>
              <td style="font-size:12.5px;<?= $overdue?'color:var(--red);font-weight:600':'' ?>"><?= fDate($t['due_date']) ?><?= $overdue?' ⚠':'' ?></td>
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
        <div class="card-title">Documents <span style="font-size:13px;color:var(--text3);font-weight:400">(<?= count($proj_docs) ?>)</span></div>
        <a href="documents.php?project_id=<?= $single['id'] ?>&new=1" class="btn btn-ghost btn-sm">↑ Upload</a>
      </div>
      <?php renderAttachWidget('project', $single['id']); ?>
      <?php if (empty($proj_docs)): ?>
        <div class="empty-state"><div class="icon">📄</div><p>No documents attached. Use ↑ Upload above for full documents, or drag &amp; drop below for quick attachments.</p></div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:8px">
          <?php foreach ($proj_docs as $d):
            $ext = strtolower(pathinfo($d['original_name'],PATHINFO_EXTENSION));
            $dicon = match(true) {
              in_array($ext,['pdf'])         => '📕',
              in_array($ext,['doc','docx'])  => '📘',
              in_array($ext,['xls','xlsx'])  => '📗',
              in_array($ext,['jpg','jpeg','png','gif']) => '🖼',
              default => '📄'
            };
          ?>
          <div style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:11px 14px;display:flex;align-items:center;gap:12px">
            <span style="font-size:22px;flex-shrink:0"><?= $dicon ?></span>
            <div style="flex:1;min-width:0">
              <div style="font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($d['title']) ?></div>
              <div style="font-size:11px;color:var(--text3)"><?= h($d['original_name']) ?> · <?= formatSize($d['file_size']) ?></div>
            </div>
            <?php if (strtolower($ext)==='pdf'): ?>
            <a href="documents.php?view_pdf=<?= $d['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Preview PDF" style="color:var(--red)" target="_blank">👁</a>
            <?php endif; ?>
            <a href="documents.php?download=<?= $d['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Download">↓</a>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- RIGHT SIDEBAR -->
  <div>
    <!-- Task Status Summary -->
    <div class="card" style="margin-bottom:14px">
      <div class="card-title" style="margin-bottom:14px">Task Summary</div>
      <?php
        $todo_c   = count(array_filter($proj_tasks,fn($t)=>$t['status']==='todo'));
        $inprog_c = count(array_filter($proj_tasks,fn($t)=>$t['status']==='in_progress'));
        $review_c = count(array_filter($proj_tasks,fn($t)=>$t['status']==='review'));
      ?>
      <?php foreach ([['To Do',$todo_c,'#6366f1'],['In Progress',$inprog_c,'#f59e0b'],['Review',$review_c,'#8b5cf6'],['Done',$pdone,'#10b981']] as [$l,$v,$c]): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border)">
        <div style="display:flex;align-items:center;gap:8px">
          <div style="width:8px;height:8px;border-radius:50%;background:<?= $c ?>;flex-shrink:0"></div>
          <span style="font-size:13px;color:var(--text2)"><?= $l ?></span>
        </div>
        <span style="font-size:13px;font-weight:700;color:<?= $c ?>"><?= $v ?></span>
      </div>
      <?php endforeach; ?>
      <div style="display:flex;justify-content:space-between;padding:8px 0">
        <span style="font-size:13px;color:var(--text2);font-weight:600">Total</span>
        <span style="font-size:13px;font-weight:700"><?= $ptotal ?></span>
      </div>
    </div>

    <!-- Team Members -->
    <div class="card">
      <div class="card-title" style="margin-bottom:14px">Team Members</div>
      <?php if (empty($proj_members_list)): ?>
        <p style="font-size:13px;color:var(--text3)">No members assigned.</p>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px">
          <?php foreach ($proj_members_list as $m):
            $init = strtoupper(substr($m['name'],0,1));
            $role_colors=['admin'=>'#ef4444','manager'=>'#f59e0b','member'=>'#6366f1'];
            $rc = $role_colors[$m['role']]??'#94a3b8';
          ?>
          <div style="display:flex;align-items:center;gap:10px">
            <div class="avatar" style="background:<?= $rc ?>"><?= $init ?></div>
            <div>
              <div style="font-size:13px;font-weight:600;color:var(--text)"><?= h($m['name']) ?></div>
              <div style="font-size:11px;color:var(--text3);text-transform:capitalize"><?= h($m['role']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function syncProgress(val){
  val = Math.max(0, Math.min(100, parseInt(val) || 0));
  var fill = document.getElementById('prog-fill');
  var disp = document.getElementById('pct-display');
  // Don't overwrite the input itself — user is typing
  if (fill) fill.style.width = val + '%';
  if (disp) disp.textContent = val + '%';
  // Update the progress bar color based on value
  if (fill) fill.style.background = val >= 75 ? '#10b981' : val >= 40 ? '#f59e0b' : '#f97316';
}
function clickBar(e, el){
  var rect = el.getBoundingClientRect();
  var pct  = Math.round((e.clientX - rect.left) / rect.width * 100);
  pct = Math.max(0, Math.min(100, pct));
  var inp = document.getElementById('prog-val');
  if (inp) inp.value = pct;
  syncProgress(pct);
}
</script>

<?php else: // ════ PROJECT LIST ════ ?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px;flex-wrap:wrap">
  <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;flex:1">
    <div class="search-box" style="flex:1;max-width:280px">
      <span style="color:var(--text3)">🔍</span>
      <input type="text" name="q" placeholder="Search projects…" value="<?= h($search) ?>">
    </div>
    <select name="status" class="form-control" style="width:auto" onchange="this.form.submit()">
      <option value="">All Status</option>
      <?php foreach (['planning','active','on_hold','completed','cancelled'] as $s): ?>
      <option value="<?= $s ?>" <?= $status_filter===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <?php if (isManager()): ?>
  <button class="btn btn-primary" onclick="openModal('modal-project')" style="flex-shrink:0">＋ <span>New Project</span></button>
  <?php endif; ?>
</div>

<?php if (empty($projects)): ?>
<div class="card">
  <div class="empty-state">
    <div class="icon">📁</div>
    <p>No projects found.<?= isManager() ? ' <a href="#" onclick="openModal(\'modal-project\')" style="color:var(--orange)">Create the first one</a>' : '' ?></p>
  </div>
</div>
<?php else: ?>
<div class="proj-list">
  <?php foreach ($projects as $p):
    // Always use manually saved progress. Task done count is informational only.
    $progress = (int)$p['progress'];
    $sc = statusColor($p['status']);
    $pc = statusColor($p['priority']);
    $sym = currSymbol($p['currency'] ?? 'LKR');
    $pbar_color = $progress >= 75 ? '#10b981' : ($progress >= 40 ? '#f59e0b' : '#f97316');
    $is_overdue = $p['due_date'] && $p['due_date'] < date('Y-m-d') && $p['status'] !== 'completed' && $p['status'] !== 'cancelled';
    $status_icon = match($p['status']) { 'completed'=>'✅','cancelled'=>'🚫','on_hold'=>'⏸',default=>'📁' };
  ?>
  <div class="proj-card" style="--pc:<?= $sc ?>" onclick="location.href='projects.php?view=<?= $p['id'] ?>'">
    <div class="proj-card-top">

      <!-- Title row -->
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:10px">
        <div style="flex:1;min-width:0">
          <div style="font-size:15px;font-weight:700;font-family:var(--font-display);color:var(--text);margin-bottom:7px;line-height:1.3;word-break:break-word"><?= h($p['title']) ?></div>
          <div style="display:flex;gap:5px;flex-wrap:wrap">
            <span class="badge" style="background:<?= $sc ?>18;color:<?= $sc ?>;font-size:11px"><?= ucfirst(str_replace('_',' ',$p['status'])) ?></span>
            <span class="badge" style="background:<?= $pc ?>18;color:<?= $pc ?>;font-size:11px"><?= priorityIcon($p['priority']) ?> <?= ucfirst($p['priority']) ?></span>
          </div>
        </div>
        <div style="width:34px;height:34px;border-radius:8px;background:<?= $sc ?>16;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0"><?= $status_icon ?></div>
      </div>

      <!-- Client & Budget -->
      <?php if ($p['client_name'] || $p['budget']): ?>
      <div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:11px">
        <?php if ($p['client_name']): ?>
        <span style="font-size:12px;color:var(--text2);display:flex;align-items:center;gap:4px">🏢 <?= h($p['client_name']) ?></span>
        <?php endif; ?>
        <?php if ($p['budget']): ?>
        <span style="font-size:12px;color:var(--text2);display:flex;align-items:center;gap:4px;font-weight:600">💰 <?= $sym.number_format((float)$p['budget'],2) ?></span>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Progress -->
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
        <span style="font-size:10.5px;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:.05em">Progress</span>
        <span style="font-size:12px;font-weight:800;color:<?= $pbar_color ?>"><?= $progress ?>%</span>
      </div>
      <div style="height:5px;background:var(--bg4);border-radius:99px;overflow:hidden">
        <div style="height:100%;width:<?= $progress ?>%;background:<?= $pbar_color ?>;border-radius:99px;transition:width .4s ease"></div>
      </div>
    </div>

    <!-- Footer -->
    <div class="proj-card-bottom">
      <span style="font-size:11.5px;color:var(--text3)">
        📋 <?= $p['total_tasks'] ?> task<?= $p['total_tasks']!=1?'s':'' ?> &nbsp;·&nbsp; 👥 <?= $p['member_count'] ?>
      </span>
      <span style="font-size:11.5px;font-weight:600;color:<?= $is_overdue?'var(--red)':'var(--text3)' ?>">
        <?= $p['due_date'] ? ($is_overdue ? '⚠ Overdue' : fDate($p['due_date'],'M j, Y')) : 'No deadline' ?>
      </span>
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
          <textarea name="description" class="form-control"><?= h($edit_proj['description'] ?? '') ?></textarea>
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
              <?php foreach (['low','medium','high','urgent'] as $pv): ?>
              <option value="<?= $pv ?>" <?= ($edit_proj['priority']??'medium')===$pv?'selected':'' ?>><?= ucfirst($pv) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <?php foreach (['planning','active','on_hold','completed','cancelled'] as $sv): ?>
              <option value="<?= $sv ?>" <?= ($edit_proj['status']??'planning')===$sv?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$sv)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Currency</label>
            <select name="currency" class="form-control" id="proj-currency">
              <?php foreach (['LKR'=>'Rs. — Sri Lankan Rupee','INR'=>'₹ — Indian Rupee','USD'=>'$ — US Dollar','EUR'=>'€ — Euro','GBP'=>'£ — British Pound'] as $cv=>$cl): ?>
              <option value="<?= $cv ?>" <?= ($edit_proj['currency']??'LKR')===$cv?'selected':'' ?>><?= $cl ?></option>
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
          <div style="display:flex;gap:0">
            <span id="proj-sym" style="background:var(--bg4);border:1px solid var(--border);border-right:none;border-radius:var(--radius-sm) 0 0 var(--radius-sm);padding:9px 12px;font-size:13px;color:var(--text2);white-space:nowrap">Rs.</span>
            <input type="number" name="budget" step="0.01" class="form-control" value="<?= h($edit_proj['budget']??'') ?>" placeholder="0.00" style="border-radius:0 var(--radius-sm) var(--radius-sm) 0;border-left:none">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Team Members</label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:10px;max-height:180px;overflow-y:auto">
            <?php foreach ($all_users as $u): ?>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text2);cursor:pointer;padding:4px;border-radius:4px" onmouseover="this.style.background='var(--bg4)'" onmouseout="this.style.background=''">
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

<script>
// Currency symbol updater in modal
var symMap={'LKR':'Rs.','INR':'₹','USD':'$','EUR':'€','GBP':'£'};
document.getElementById('proj-currency')?.addEventListener('change',function(){
  var sym=symMap[this.value]||this.value;
  document.getElementById('proj-sym').textContent=sym;
});
// Set on load for edit mode
(function(){
  var sel=document.getElementById('proj-currency');
  if(sel){var sym=symMap[sel.value]||sel.value;document.getElementById('proj-sym').textContent=sym;}
})();
</script>

<?php if ($edit_id): ?>
<script>document.addEventListener('DOMContentLoaded',function(){openModal('modal-project')})</script>
<?php endif; ?>

<?php endif; ?>
<?php renderLayoutEnd(); ?>